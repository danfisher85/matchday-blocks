<?php
/**
 * Plugin Name: Matchday Blocks
 * Plugin URI:  https://www.meinturnierplan.de
 * Description: Display tournament tables and matches from MeinTurnierplan using blocks.
 * Version:     1.0.0
 * Author:      MeinTurnierplan
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: matchday-blocks
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

// Define plugin constants
define('MATCHDAY_BLOCKS_VERSION', '1.0.0');
define('MATCHDAY_BLOCKS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MATCHDAY_BLOCKS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Load plugin files
 */
require_once MATCHDAY_BLOCKS_PLUGIN_DIR . 'includes/admin/class-settings.php';
require_once MATCHDAY_BLOCKS_PLUGIN_DIR . 'includes/api/class-tournament-data.php';
require_once MATCHDAY_BLOCKS_PLUGIN_DIR . 'includes/blocks/class-blocks-manager.php';

/**
 * Initialize plugin
 */
function matchday_blocks_init() {
	// Initialize admin settings
	if ( is_admin() ) {
		\Matchday_Blocks\Admin\Settings::get_instance();
	}

	// Initialize API handler
	\Matchday_Blocks\API\Tournament_Data::get_instance();

	// Initialize blocks
	\Matchday_Blocks\Blocks\Blocks_Manager::get_instance();
}
add_action('plugins_loaded', 'matchday_blocks_init');
