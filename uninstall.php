<?php
/**
 * Uninstall Matchday Blocks
 *
 * Runs when the plugin is deleted from the WordPress admin.
 * Removes all options, transients, scheduled events, and uploaded logo files.
 *
 * @package Matchday_Blocks
 * @since   1.1.0
 * @version 1.1.1
 */

// Only run when WordPress is uninstalling the plugin.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// --- Options ---
delete_option( 'matchday_blocks_tournament_id' );
delete_option( 'matchday_blocks_cache_time' );
delete_option( 'matchday_blocks_logo_hashes' );

// --- Transient ---
delete_transient( 'matchday_blocks_tournament_data' );

// --- Cron event ---
wp_clear_scheduled_hook( 'matchday_blocks_refresh_cache' );

// --- Uploaded logo files ---
$matchday_blocks_upload_dir = wp_upload_dir();
$matchday_blocks_logos_dir  = $matchday_blocks_upload_dir['basedir'] . '/matchday-blocks/logos/';

if ( is_dir( $matchday_blocks_logos_dir ) ) {
	$matchday_blocks_files = glob( $matchday_blocks_logos_dir . '*', GLOB_NOSORT );
	if ( $matchday_blocks_files ) {
		foreach ( $matchday_blocks_files as $matchday_blocks_file ) {
			if ( is_file( $matchday_blocks_file ) ) {
				wp_delete_file( $matchday_blocks_file );
			}
		}
	}
	// Remove the now-empty directories using WP_Filesystem.
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	WP_Filesystem();
	global $wp_filesystem;
	$wp_filesystem->rmdir( $matchday_blocks_logos_dir );
	$wp_filesystem->rmdir( dirname( $matchday_blocks_logos_dir ) );
}
