<?php
/**
 * Uninstall Matchday Blocks
 *
 * Runs when the plugin is deleted from the WordPress admin.
 * Removes all options, transients, scheduled events, and uploaded logo files.
 *
 * @package Matchday_Blocks
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
$upload_dir = wp_upload_dir();
$logos_dir  = $upload_dir['basedir'] . '/matchday-blocks/logos/';

if ( is_dir( $logos_dir ) ) {
	$files = glob( $logos_dir . '*', GLOB_NOSORT );
	if ( $files ) {
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
	}
	// Remove the now-empty directories.
	@rmdir( $logos_dir );
	@rmdir( dirname( $logos_dir ) );
}
