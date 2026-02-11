<?php
/**
 * Settings Admin Page
 *
 * Handles the MeinTurnierplan settings page in the WordPress admin.
 *
 * @package   Matchday_Blocks
 * @since     1.0.0
 */

namespace Matchday_Blocks\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
		exit;
}

/**
 * Settings class
 *
 * Manages the plugin settings page and options registration.
 *
 * @since 1.0.0
 */
class Settings {

	/**
	 * Plugin option key for tournament ID
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const OPTION_TOURNAMENT_ID = 'matchday_blocks_tournament_id';

	/**
	 * Settings page slug
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const PAGE_SLUG = 'matchday-blocks-settings';

	/**
	 * Settings group name
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const SETTINGS_GROUP = 'matchday_blocks_settings';

	/**
	 * Settings section ID
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const SECTION_ID = 'matchday_blocks_tournament_section';

	/**
	 * Transient key for tournament data cache
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const TRANSIENT_KEY = 'matchday_blocks_tournament_data';

	/**
	 * Instance of this class
	 *
	 * @since 1.0.0
	 * @var Settings|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @since 1.0.0
	 * @return Settings
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
	 * Hooks into WordPress to register settings and admin page.
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
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
	}

	/**
	 * Enqueue admin styles
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix The current admin page.
	 * @return void
	 */
	public function enqueue_admin_styles( $hook_suffix ) {
		// Only load on our settings page.
		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'matchday-blocks-admin',
			MATCHDAY_BLOCKS_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			MATCHDAY_BLOCKS_VERSION
		);
	}

	/**
	 * Register the admin settings page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_admin_page() {
		add_menu_page(
			__( 'MeinTurnierplan Settings', 'matchday-blocks' ),
			__( 'MeinTurnierplan', 'matchday-blocks' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' ),
			'dashicons-list-view',
			65
		);
	}

	/**
	 * Register plugin settings and fields
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_settings() {
		// Register the tournament ID setting.
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_TOURNAMENT_ID,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		// Add settings section.
		add_settings_section(
			self::SECTION_ID,
			__( 'Tournament Configuration', 'matchday-blocks' ),
			array( $this, 'render_section_description' ),
			self::PAGE_SLUG
		);

		// Add tournament ID field.
		add_settings_field(
			self::OPTION_TOURNAMENT_ID,
			__( 'Tournament ID', 'matchday-blocks' ),
			array( $this, 'render_tournament_id_field' ),
			self::PAGE_SLUG,
			self::SECTION_ID
		);
	}

	/**
	 * Render the settings section description
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_section_description() {
		echo '<p>' . esc_html__(
			'Enter your MeinTurnierplan tournament ID to fetch and display tournament data on your site.',
			'matchday-blocks'
		) . '</p>';
	}

	/**
	 * Render the tournament ID input field
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_tournament_id_field() {
		$tournament_id = $this->get_tournament_id();
		?>
		<input
			type="text"
			name="<?php echo esc_attr( self::OPTION_TOURNAMENT_ID ); ?>"
			id="<?php echo esc_attr( self::OPTION_TOURNAMENT_ID ); ?>"
			value="<?php echo esc_attr( $tournament_id ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'Enter tournament ID', 'matchday-blocks' ); ?>"
		/>
		<p class="description">
			<?php esc_html_e( 'The tournament ID from MeinTurnierplan (e.g., 12345)', 'matchday-blocks' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the settings page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_settings_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle settings update.
		$this->maybe_handle_settings_update();

		// Show admin notices.
		settings_errors( 'matchday_blocks_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::SETTINGS_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button( __( 'Save Settings', 'matchday-blocks' ) );
				?>
			</form>

			<?php $this->render_status_card(); ?>
		</div>
		<?php
	}

	/**
	 * Handle settings update actions
	 *
	 * Clears cache when settings are saved.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function maybe_handle_settings_update() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['settings-updated'] ) ) {
			// Clear the cached tournament data.
			delete_transient( self::TRANSIENT_KEY );

			add_settings_error(
				'matchday_blocks_messages',
				'matchday_blocks_message',
				__( 'Settings saved. Tournament data cache has been cleared.', 'matchday-blocks' ),
				'success'
			);
		}
	}

	/**
	 * Render the tournament status card
	 *
	 * Shows tournament ID and cache status.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_status_card() {
		$tournament_id = $this->get_tournament_id();

		if ( empty( $tournament_id ) ) {
			return;
		}
		?>
		<div class="card matchday-blocks-status-card">
			<h2><?php esc_html_e( 'Tournament Data Status', 'matchday-blocks' ); ?></h2>

			<p>
				<strong><?php esc_html_e( 'Tournament ID:', 'matchday-blocks' ); ?></strong>
				<?php echo esc_html( $tournament_id ); ?>
			</p>

			<p>
				<strong><?php esc_html_e( 'API URL:', 'matchday-blocks' ); ?></strong>
				<code><?php echo esc_url( $this->get_api_url( $tournament_id ) ); ?></code>
			</p>

			<?php $this->render_cache_status(); ?>
		</div>
		<?php
	}

	/**
	 * Render cache status message
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_cache_status() {
		$cached_data = get_transient( self::TRANSIENT_KEY );

		if ( $cached_data ) {
			echo '<p class="matchday-blocks-status-success">✓ ' . esc_html__(
				'Tournament data is cached and ready to use.',
				'matchday-blocks'
			) . '</p>';
		} else {
			echo '<p class="matchday-blocks-status-warning">⚠ ' . esc_html__(
				'Tournament data is not cached. It will be fetched on the first request.',
				'matchday-blocks'
			) . '</p>';
		}
	}

	/**
	 * Get the tournament ID from options
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_tournament_id() {
		return get_option( self::OPTION_TOURNAMENT_ID, '' );
	}

	/**
	 * Get the API URL for a tournament ID
	 *
	 * @since 1.0.0
	 * @param string $tournament_id Tournament ID.
	 * @return string
	 */
	private function get_api_url( $tournament_id ) {
		return 'https://www.meinturnierplan.de/json/json.php?id=' . $tournament_id;
	}
}
