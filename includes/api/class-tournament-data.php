<?php
/**
 * Tournament Data API Handler
 *
 * Handles fetching and caching tournament data from MeinTurnierplan API.
 *
 * @package   Matchday_Blocks
 * @since     1.0.0
 * @version   1.1.4
 */

namespace Matchday_Blocks\API;

use WP_Error;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tournament_Data class
 *
 * Manages API communication with MeinTurnierplan and data caching.
 *
 * @since 1.0.0
 */
class Tournament_Data {

	/**
	 * Transient key for tournament data cache
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const TRANSIENT_KEY = 'matchday_blocks_tournament_data';

	/**
	 * WP-Cron hook name for background cache refresh
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const CRON_HOOK = 'matchday_blocks_refresh_cache';

	/**
	 * Option key for local logo URL hashes (used to detect remote changes)
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const LOGO_HASHES_OPTION = 'matchday_blocks_logo_hashes';

	/**
	 * Option key for tournament ID
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const OPTION_TOURNAMENT_ID = 'matchday_blocks_tournament_id';

	/**
	 * Option key for cache time
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const OPTION_CACHE_TIME = 'matchday_blocks_cache_time';

	/**
	 * API base URL
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const API_BASE_URL = 'https://www.meinturnierplan.de/json/json.php';

	/**
	 * API request timeout (in seconds)
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const API_TIMEOUT = 15;

	/**
	 * Transient key for the last API error.
	 *
	 * While it exists, visitors are not made to wait for another API request.
	 *
	 * @since 1.1.4
	 * @var string
	 */
	const ERROR_TRANSIENT_KEY = 'matchday_blocks_fetch_error';

	/**
	 * How long an API error is remembered (in seconds)
	 *
	 * @since 1.1.4
	 * @var int
	 */
	const ERROR_CACHE_TIME = 5 * MINUTE_IN_SECONDS;

	/**
	 * Option key for the last successfully fetched tournament data
	 *
	 * Served when the API cannot be reached.
	 *
	 * @since 1.1.4
	 * @var string
	 */
	const LAST_GOOD_OPTION = 'matchday_blocks_last_good_data';

	/**
	 * Logo sizes the blocks use, per data collection.
	 *
	 * Only these sizes are downloaded; all other logo URLs are dropped.
	 *
	 * @since 1.1.4
	 * @var array
	 */
	const LOGO_SIZES = array(
		'participants' => array( 'lx95w', 'lx160' ),
		'groups'       => array( 'lx95' ),
	);

	/**
	 * Maximum size of a downloaded logo (in bytes)
	 *
	 * @since 1.1.4
	 * @var int
	 */
	const LOGO_MAX_BYTES = 2 * MB_IN_BYTES;

	/**
	 * Instance of this class
	 *
	 * @since 1.0.0
	 * @var Tournament_Data|null
	 */
	private static $instance = null;

	/**
	 * Tournament data already loaded during the current request
	 *
	 * Lets several blocks on one page share a single lookup.
	 *
	 * @since 1.1.4
	 * @var array|WP_Error|null
	 */
	private $request_data = null;

	/**
	 * Get singleton instance
	 *
	 * @since 1.0.0
	 * @return Tournament_Data
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @since   1.0.0
	 * @version 1.1.0
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'update_option_' . self::OPTION_TOURNAMENT_ID, array( $this, 'clear_cache_on_option_update' ), 10, 2 );
		add_action( 'update_option_' . self::OPTION_CACHE_TIME, array( $this, 'reschedule_cron_on_cache_time_update' ), 10, 2 );
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedule' ) );
		add_action( 'init', array( $this, 'schedule_cron' ) );
		add_action( self::CRON_HOOK, array( $this, 'run_cron_refresh' ) );
	}

	/**
	 * Get tournament data from API or cache
	 *
	 * Fetches tournament data from the MeinTurnierplan API and caches it.
	 * Returns cached data if available and not forcing refresh.
	 *
	 * @since   1.0.0
	 * @version 1.1.4
	 * @param bool $force_refresh Whether to force refresh the cache.
	 * @return array|WP_Error Tournament data array or WP_Error on failure.
	 */
	public function get_tournament_data( $force_refresh = false ) {
		if ( ! $force_refresh && null !== $this->request_data ) {
			return $this->request_data;
		}

		$this->request_data = $this->load_tournament_data( $force_refresh );

		return $this->request_data;
	}

	/**
	 * Load tournament data from the cache or the API.
	 *
	 * When the API fails, the error is remembered for a few minutes so that
	 * visitors are not made to wait for repeated requests, and the last
	 * successfully fetched data is served instead.
	 *
	 * @since 1.1.4
	 * @param bool $force_refresh Whether to skip the cache.
	 * @return array|WP_Error Tournament data array or WP_Error on failure.
	 */
	private function load_tournament_data( $force_refresh ) {
		$tournament_id = $this->get_tournament_id();

		// Return error if no tournament ID is set.
		if ( empty( $tournament_id ) ) {
			return new WP_Error(
				'no_tournament_id',
				__( 'No tournament ID configured. Please set it on the Matchday settings page.', 'matchday-blocks' )
			);
		}

		if ( ! $force_refresh ) {
			$cached_data = $this->get_cached_data();
			if ( false !== $cached_data ) {
				return $cached_data;
			}

			// The API failed recently: don't try again yet.
			$last_error = $this->get_last_error();
			if ( false !== $last_error ) {
				return $this->get_fallback_data( $tournament_id, new WP_Error( 'api_error', $last_error ) );
			}
		}

		$data = $this->fetch_from_api( $tournament_id );

		if ( is_wp_error( $data ) ) {
			set_transient( self::ERROR_TRANSIENT_KEY, $data->get_error_message(), self::ERROR_CACHE_TIME );
			return $this->get_fallback_data( $tournament_id, $data );
		}

		delete_transient( self::ERROR_TRANSIENT_KEY );

		return $data;
	}

	/**
	 * Get the last successfully fetched data for a tournament, or the given error.
	 *
	 * @since 1.1.4
	 * @param string   $tournament_id Tournament ID.
	 * @param WP_Error $error         Error to return when there is no saved data.
	 * @return array|WP_Error
	 */
	private function get_fallback_data( $tournament_id, WP_Error $error ) {
		$last_good = get_option( self::LAST_GOOD_OPTION );

		if (
			is_array( $last_good ) &&
			isset( $last_good['tournament_id'], $last_good['data'] ) &&
			(string) $tournament_id === (string) $last_good['tournament_id']
		) {
			return $last_good['data'];
		}

		return $error;
	}

	/**
	 * Get the message of the last API error, if it happened recently.
	 *
	 * @since 1.1.4
	 * @return string|false Error message, or false if there was no recent error.
	 */
	public function get_last_error() {
		return get_transient( self::ERROR_TRANSIENT_KEY );
	}

	/**
	 * Fetch tournament data from API
	 *
	 * @since   1.0.0
	 * @version 1.1.0
	 * @param string $tournament_id Tournament ID.
	 * @return array|WP_Error Tournament data or error.
	 */
	private function fetch_from_api( $tournament_id ) {
		$api_url = $this->build_api_url( $tournament_id );

		// Make API request.
		$response = wp_safe_remote_get(
			$api_url,
			array(
				'timeout' => self::API_TIMEOUT,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		// Check for request errors.
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_error',
				sprintf(
					/* translators: %s: Error message */
					__( 'Failed to fetch tournament data: %s', 'matchday-blocks' ),
					$response->get_error_message()
				)
			);
		}

		// Check response code.
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return new WP_Error(
				'api_error',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'API returned error code: %d', 'matchday-blocks' ),
					$response_code
				)
			);
		}

		// Parse response body.
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Check if JSON is valid.
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
			return new WP_Error(
				'json_error',
				__( 'Failed to parse tournament data. Invalid JSON received.', 'matchday-blocks' )
			);
		}

		// Download and store logos locally to avoid exposing visitor IPs to third-party servers.
		$data = $this->localize_logos( $data );

		// Cache the data, and keep a copy to fall back on if the API fails later.
		$this->set_cached_data( $data );
		update_option(
			self::LAST_GOOD_OPTION,
			array(
				'tournament_id' => $tournament_id,
				'data'          => $data,
			),
			false
		);

		return $data;
	}

	/**
	 * Build API URL with tournament ID
	 *
	 * @since 1.0.0
	 * @param string $tournament_id Tournament ID.
	 * @return string API URL.
	 */
	private function build_api_url( $tournament_id ) {
		return add_query_arg( 'id', $tournament_id, self::API_BASE_URL );
	}

	/**
	 * Get cached tournament data
	 *
	 * @since 1.0.0
	 * @return array|false Cached data or false if not cached.
	 */
	public function get_cached_data() {
		return get_transient( self::TRANSIENT_KEY );
	}

	/**
	 * Set cached tournament data
	 *
	 * @since 1.0.0
	 * @param array $data Tournament data to cache.
	 * @return bool True if cached successfully.
	 */
	private function set_cached_data( $data ) {
		$cache_time_hours = $this->get_cache_time();
		$cache_expiration = $cache_time_hours * HOUR_IN_SECONDS;
		return set_transient( self::TRANSIENT_KEY, $data, $cache_expiration );
	}

	/**
	 * Clear tournament data cache and local logo files
	 *
	 * @since   1.0.0
	 * @version 1.1.4
	 * @return bool True if anything was cleared.
	 */
	public function clear_cache() {
		$this->request_data = null;
		$this->clear_logo_cache();
		$had_fallback = delete_option( self::LAST_GOOD_OPTION );
		$had_error    = delete_transient( self::ERROR_TRANSIENT_KEY );
		$had_cache    = delete_transient( self::TRANSIENT_KEY );

		return $had_cache || $had_error || $had_fallback;
	}

	/**
	 * Check if tournament data is cached
	 *
	 * @since 1.0.0
	 * @return bool True if cached, false otherwise.
	 */
	public function is_cached() {
		return false !== $this->get_cached_data();
	}

	/**
	 * Get tournament ID from options
	 *
	 * @since 1.0.0
	 * @return string Tournament ID.
	 */
	private function get_tournament_id() {
		return get_option( self::OPTION_TOURNAMENT_ID, '' );
	}

	/**
	 * Get cache time from options
	 *
	 * @since 1.0.0
	 * @return int Cache time in hours.
	 */
	private function get_cache_time() {
		return absint( get_option( self::OPTION_CACHE_TIME, 1 ) );
	}

	/**
	 * Clear cache when tournament ID option is updated
	 *
	 * @since 1.0.0
	 * @param mixed $old_value Old option value.
	 * @param mixed $new_value New option value.
	 * @return void
	 */
	public function clear_cache_on_option_update( $old_value, $new_value ) {
		if ( $old_value !== $new_value ) {
			$this->clear_cache();
		}
	}

	/**
	 * Register a custom cron schedule matching the configured cache time.
	 *
	 * @since 1.1.0
	 * @param array $schedules Existing cron schedules.
	 * @return array Modified schedules.
	 */
	public function add_cron_schedule( array $schedules ): array {
		$hours    = $this->get_cache_time();
		$key      = 'matchday_blocks_every_' . $hours . 'h';
		$schedules[ $key ] = array(
			'interval' => $hours * HOUR_IN_SECONDS,
			/* translators: %d: number of hours */
			'display'  => sprintf( __( 'Every %d hour(s) (Matchday Blocks)', 'matchday-blocks' ), $hours ),
		);
		return $schedules;
	}

	/**
	 * Schedule the background cache refresh cron event if not already scheduled.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function schedule_cron(): void {
		if ( empty( $this->get_tournament_id() ) ) {
			return;
		}
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			$hours = $this->get_cache_time();
			wp_schedule_event( time(), 'matchday_blocks_every_' . $hours . 'h', self::CRON_HOOK );
		}
	}

	/**
	 * Unschedule all background cache refresh cron events.
	 *
	 * @since   1.1.0
	 * @version 1.1.4
	 * @return void
	 */
	public function unschedule_cron(): void {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Reschedule the cron when the cache time setting changes.
	 *
	 * @since 1.1.0
	 * @param mixed $old_value Old option value.
	 * @param mixed $new_value New option value.
	 * @return void
	 */
	public function reschedule_cron_on_cache_time_update( $old_value, $new_value ): void {
		if ( $old_value !== $new_value ) {
			$this->unschedule_cron();
			// schedule_cron() will register a new event on the next init.
		}
	}

	/**
	 * Background cron callback: refresh tournament data cache.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function run_cron_refresh(): void {
		$this->get_tournament_data( true );
	}

	/**
	 * Replace participant and group logo URLs with locally stored copies.
	 *
	 * Only the sizes listed in LOGO_SIZES are kept. A logo that cannot be
	 * stored locally is dropped rather than loaded from the remote server,
	 * so visitor browsers never contact third-party servers. Logo files that
	 * are no longer used are deleted.
	 *
	 * @since 1.1.4
	 * @param array $data Tournament data from the API.
	 * @return array Tournament data with local logo URLs.
	 */
	private function localize_logos( array $data ): array {
		$filesystem = $this->get_filesystem();
		$stored     = get_option( self::LOGO_HASHES_OPTION, array() );
		$stored     = is_array( $stored ) ? $stored : array();
		$index      = array();
		$prefixes   = array(
			'participants' => 'team',
			'groups'       => 'group',
		);

		foreach ( $prefixes as $collection => $prefix ) {
			if ( empty( $data[ $collection ] ) || ! is_array( $data[ $collection ] ) ) {
				continue;
			}

			foreach ( $data[ $collection ] as $item_key => $item ) {
				if ( ! is_array( $item ) || ! isset( $item['logo'] ) ) {
					continue;
				}

				$remote_logos = is_array( $item['logo'] ) ? $item['logo'] : array();
				$item_id      = isset( $item['displayId'] ) ? $item['displayId'] : $item_key;
				$local_logos  = array();

				foreach ( self::LOGO_SIZES[ $collection ] as $size ) {
					if ( ! $filesystem || empty( $remote_logos[ $size ] ) || ! is_string( $remote_logos[ $size ] ) ) {
						continue;
					}

					$base      = $prefix . '-' . sanitize_key( (string) $item_id ) . '-' . $size;
					$local_url = $this->get_local_image_url( $filesystem, $remote_logos[ $size ], $base, $stored, $index );

					if ( '' !== $local_url ) {
						$local_logos[ $size ] = $local_url;
					}
				}

				$data[ $collection ][ $item_key ]['logo'] = $local_logos;
			}
		}

		update_option( self::LOGO_HASHES_OPTION, $index, false );

		if ( $filesystem ) {
			$this->prune_logo_files( $filesystem, wp_list_pluck( $index, 'file' ) );
		}

		return $data;
	}

	/**
	 * Get the local URL of a remote logo, downloading it if needed.
	 *
	 * Re-downloads only if the source URL has changed since the last download.
	 * If a download fails, the previous local copy is used when there is one.
	 *
	 * @since   1.0.0
	 * @version 1.1.4
	 * @param \WP_Filesystem_Base $filesystem Filesystem instance.
	 * @param string              $remote_url Remote image URL.
	 * @param string              $base       Base filename (without extension).
	 * @param array               $stored     Logo index saved by the previous fetch.
	 * @param array               $index      Logo index being built; updated in place.
	 * @return string Local URL, or an empty string if no local copy is available.
	 */
	private function get_local_image_url( $filesystem, string $remote_url, string $base, array $stored, array &$index ): string {
		$logos_dir = $this->get_logos_dir();
		$previous  = isset( $stored[ $base ]['hash'], $stored[ $base ]['file'] ) ? $stored[ $base ] : null;
		$has_copy  = $previous && $filesystem->exists( $logos_dir . $previous['file'] );
		$hash      = md5( $remote_url );

		// Source URL unchanged and the file is still there: nothing to download.
		if ( $has_copy && $hash === $previous['hash'] ) {
			$index[ $base ] = $previous;
			return $this->get_logos_url() . $previous['file'];
		}

		$file = $this->download_logo( $filesystem, $remote_url, $base );

		if ( '' === $file ) {
			if ( $has_copy ) {
				// Keep the old hash so the download is retried on the next fetch.
				$index[ $base ] = $previous;
				return $this->get_logos_url() . $previous['file'];
			}
			return '';
		}

		$index[ $base ] = array(
			'hash' => $hash,
			'file' => $file,
		);

		return $this->get_logos_url() . $file;
	}

	/**
	 * Download a logo and store it in the logos directory.
	 *
	 * The file is stored only if it is a JPEG, PNG, GIF or WebP image, and
	 * its extension is taken from the image content, not from the URL.
	 *
	 * @since 1.1.4
	 * @param \WP_Filesystem_Base $filesystem Filesystem instance.
	 * @param string              $remote_url Remote image URL.
	 * @param string              $base       Base filename (without extension).
	 * @return string Stored file name, or an empty string on failure.
	 */
	private function download_logo( $filesystem, string $remote_url, string $base ): string {
		$response = wp_safe_remote_get(
			$remote_url,
			array(
				'timeout'             => 10,
				'limit_response_size' => self::LOGO_MAX_BYTES,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return '';
		}

		$image_data = wp_remote_retrieve_body( $response );

		// A body that reached the size limit was cut off.
		if ( strlen( $image_data ) >= self::LOGO_MAX_BYTES ) {
			return '';
		}

		$extension = $this->get_image_extension( $image_data );
		if ( '' === $extension ) {
			return '';
		}

		$logos_dir = $this->get_logos_dir();
		if ( ! wp_mkdir_p( $logos_dir ) ) {
			return '';
		}

		$file = sanitize_file_name( $base . '.' . $extension );
		if ( ! $filesystem->put_contents( $logos_dir . $file, $image_data, FS_CHMOD_FILE ) ) {
			return '';
		}

		return $file;
	}

	/**
	 * Detect the file extension of raster image data.
	 *
	 * @since 1.1.4
	 * @param string $image_data Raw image data.
	 * @return string One of jpg, png, gif or webp, or an empty string if the
	 *                data is not one of these image types.
	 */
	private function get_image_extension( string $image_data ): string {
		if ( strlen( $image_data ) < 12 ) {
			return '';
		}

		// Check the file signature first; getimagesizefromstring() emits
		// notices for some non-image input.
		$signatures = array(
			'jpg'  => "\xFF\xD8\xFF",
			'png'  => "\x89PNG\r\n\x1A\n",
			'gif'  => 'GIF8',
			'webp' => 'RIFF',
		);
		$extension  = '';
		foreach ( $signatures as $candidate => $signature ) {
			if ( 0 === strpos( $image_data, $signature ) ) {
				$extension = $candidate;
				break;
			}
		}
		if ( 'webp' === $extension && 'WEBP' !== substr( $image_data, 8, 4 ) ) {
			$extension = '';
		}
		if ( '' === $extension ) {
			return '';
		}

		// Confirm that the data really is an image of that type.
		$mime_types = array(
			'jpg'  => 'image/jpeg',
			'png'  => 'image/png',
			'gif'  => 'image/gif',
			'webp' => 'image/webp',
		);
		$info       = getimagesizefromstring( $image_data );
		if ( ! $info || ! isset( $info['mime'] ) || $mime_types[ $extension ] !== $info['mime'] ) {
			return '';
		}

		return $extension;
	}

	/**
	 * Delete logo files that are not in the given list.
	 *
	 * @since 1.1.4
	 * @param \WP_Filesystem_Base $filesystem Filesystem instance.
	 * @param string[]            $keep       File names to keep.
	 * @return void
	 */
	private function prune_logo_files( $filesystem, array $keep ): void {
		$logos_dir = $this->get_logos_dir();

		if ( ! $filesystem->is_dir( $logos_dir ) ) {
			return;
		}

		$files = $filesystem->dirlist( $logos_dir );
		if ( ! $files ) {
			return;
		}

		foreach ( $files as $file_name => $file_info ) {
			if ( 'f' === $file_info['type'] && ! in_array( $file_name, $keep, true ) ) {
				$filesystem->delete( $logos_dir . $file_name );
			}
		}
	}

	/**
	 * Get the path of the logos directory, with a trailing slash.
	 *
	 * @since 1.1.4
	 * @return string
	 */
	private function get_logos_dir(): string {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'] . '/matchday-blocks/logos/';
	}

	/**
	 * Get the URL of the logos directory, with a trailing slash.
	 *
	 * @since 1.1.4
	 * @return string
	 */
	private function get_logos_url(): string {
		$upload_dir = wp_upload_dir();
		return $upload_dir['baseurl'] . '/matchday-blocks/logos/';
	}

	/**
	 * Delete all locally cached logo files and stored URL hashes.
	 *
	 * @since   1.0.0
	 * @version 1.1.4
	 * @return void
	 */
	private function clear_logo_cache(): void {
		delete_option( self::LOGO_HASHES_OPTION );

		$filesystem = $this->get_filesystem();
		if ( ! $filesystem ) {
			return;
		}

		$this->prune_logo_files( $filesystem, array() );
	}

	/**
	 * Initialize and return WP_Filesystem.
	 *
	 * @since 1.0.0
	 * @return \WP_Filesystem_Base|false
	 */
	private function get_filesystem() {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem;
	}
}
