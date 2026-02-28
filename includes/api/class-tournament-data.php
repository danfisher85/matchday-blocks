<?php
/**
 * Tournament Data API Handler
 *
 * Handles fetching and caching tournament data from MeinTurnierplan API.
 *
 * @package   Matchday_Blocks
 * @since     1.0.0
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
	 * Instance of this class
	 *
	 * @since 1.0.0
	 * @var Tournament_Data|null
	 */
	private static $instance = null;

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
	 * @since 1.0.0
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'update_option_' . self::OPTION_TOURNAMENT_ID, array( $this, 'clear_cache_on_option_update' ), 10, 2 );
	}

	/**
	 * Get tournament data from API or cache
	 *
	 * Fetches tournament data from the MeinTurnierplan API and caches it.
	 * Returns cached data if available and not forcing refresh.
	 *
	 * @since 1.0.0
	 * @param bool $force_refresh Whether to force refresh the cache.
	 * @return array|WP_Error Tournament data array or WP_Error on failure.
	 */
	public function get_tournament_data( $force_refresh = false ) {
		$tournament_id = $this->get_tournament_id();

		// Return error if no tournament ID is set.
		if ( empty( $tournament_id ) ) {
			return new WP_Error(
				'no_tournament_id',
				__( 'No tournament ID configured. Please set it in MeinTurnierplan Settings.', 'matchday-blocks' )
			);
		}

		// Try to get cached data if not forcing refresh.
		if ( ! $force_refresh ) {
			$cached_data = $this->get_cached_data();
			if ( false !== $cached_data ) {
				return $cached_data;
			}
		}

		// Fetch fresh data from API.
		return $this->fetch_from_api( $tournament_id );
	}

	/**
	 * Fetch tournament data from API
	 *
	 * @since 1.0.0
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
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error(
				'json_error',
				__( 'Failed to parse tournament data. Invalid JSON received.', 'matchday-blocks' )
			);
		}

		// Download and store team logos locally to avoid exposing visitor IPs to third-party servers.
		if ( ! empty( $data['teams'] ) ) {
			$data['teams'] = $this->cache_team_logos( $data['teams'] );
		}

		// Cache the data.
		$this->set_cached_data( $data );

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
		return add_query_arg(
			'id',
			urlencode( $tournament_id ),
			self::API_BASE_URL
		);
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
	 * @since 1.0.0
	 * @return bool True if cache was cleared.
	 */
	public function clear_cache() {
		$this->clear_logo_cache();
		return delete_transient( self::TRANSIENT_KEY );
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
	 * Download and store all team logos locally.
	 * Re-downloads only if the source URL has changed since last cache.
	 *
	 * @since 1.0.0
	 * @param array $teams Teams array from API response.
	 * @return array Teams array with logo URLs replaced by local URLs.
	 */
	private function cache_team_logos( array $teams ): array {
		foreach ( $teams as &$team ) {
			if ( ! empty( $team['logo'] ) && is_array( $team['logo'] ) ) {
				foreach ( $team['logo'] as $size => $url ) {
					if ( ! empty( $url ) ) {
						$filename          = 'team-' . sanitize_key( $team['displayId'] ) . '-' . sanitize_key( $size );
						$team['logo'][ $size ] = $this->get_local_image_url( $url, $filename );
					}
				}
			}
		}
		return $teams;
	}

	/**
	 * Download a remote image and store it locally.
	 * Returns the local URL. If URL has not changed since last download,
	 * returns the cached local URL without re-downloading.
	 *
	 * @since 1.0.0
	 * @param string $remote_url Remote image URL.
	 * @param string $filename   Base filename (without extension).
	 * @return string Local URL, or original remote URL on failure.
	 */
	private function get_local_image_url( string $remote_url, string $filename ): string {
		$upload_dir = wp_upload_dir();
		$logos_dir  = $upload_dir['basedir'] . '/matchday-blocks/logos/';
		$logos_url  = $upload_dir['baseurl'] . '/matchday-blocks/logos/';
		$ext        = pathinfo( wp_parse_url( $remote_url, PHP_URL_PATH ), PATHINFO_EXTENSION );
		$ext        = $ext ? $ext : 'png';
		$safe_name  = sanitize_file_name( $filename . '.' . $ext );
		$local_file = $logos_dir . $safe_name;
		$local_url  = $logos_url . $safe_name;

		// Check stored hash to detect whether the source URL has changed.
		$url_hashes   = get_option( self::LOGO_HASHES_OPTION, array() );
		$current_hash = md5( $remote_url );
		$stored_hash  = isset( $url_hashes[ $filename ] ) ? $url_hashes[ $filename ] : '';

		// Skip download if file exists and source URL is unchanged.
		if ( file_exists( $local_file ) && $current_hash === $stored_hash ) {
			return $local_url;
		}

		// Ensure the logos directory exists.
		wp_mkdir_p( $logos_dir );

		// Download the image from the remote server.
		$response = wp_safe_remote_get( $remote_url, array( 'timeout' => 10 ) );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return $remote_url; // Fallback to remote URL on failure.
		}

		$image_data = wp_remote_retrieve_body( $response );

		// Use WP_Filesystem to write the file (required by WordPress.org guidelines).
		$filesystem = $this->get_filesystem();
		if ( ! $filesystem ) {
			return $remote_url;
		}
		$filesystem->put_contents( $local_file, $image_data, FS_CHMOD_FILE );

		// Update the stored hash so we know this URL was last seen.
		$url_hashes[ $filename ] = $current_hash;
		update_option( self::LOGO_HASHES_OPTION, $url_hashes, false );

		return $local_url;
	}

	/**
	 * Delete all locally cached logo files and stored URL hashes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function clear_logo_cache(): void {
		delete_option( self::LOGO_HASHES_OPTION );

		$upload_dir = wp_upload_dir();
		$logos_dir  = $upload_dir['basedir'] . '/matchday-blocks/logos/';

		if ( ! is_dir( $logos_dir ) ) {
			return;
		}

		$filesystem = $this->get_filesystem();
		if ( ! $filesystem ) {
			return;
		}

		$files = glob( $logos_dir . '*' );
		if ( $files ) {
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					$filesystem->delete( $file );
				}
			}
		}
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
