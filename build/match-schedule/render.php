<?php
/**
 * Match Schedule Block Render Template
 *
 * @package Matchday_Blocks
 * @since   1.0.0
 *
 * @var array $attributes Block attributes.
 * @var string $content Block content.
 * @var WP_Block $block Block instance.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$date = isset( $attributes['date'] ) ? $attributes['date'] : '';

// Get tournament data.
$tournament_data_handler = \Matchday_Blocks\API\Tournament_Data::get_instance();
$tournament_data          = $tournament_data_handler->get_tournament_data();

// Get manager instance for helper methods.
$manager = \Matchday_Blocks\Blocks\Blocks_Manager::get_instance();

if ( is_wp_error( $tournament_data ) ) {
	echo '<div class="matchday-error"><p>' . esc_html( $tournament_data->get_error_message() ) . '</p></div>';
	return;
}

if ( empty( $tournament_data['teams'] ) ) {
	echo '<div class="matchday-error"><p>' . esc_html__( 'Tournament data is incomplete or invalid.', 'matchday-blocks' ) . '</p></div>';
	return;
}

$teams = array();
foreach ( $tournament_data['teams'] as $team ) {
	$teams[ $team['displayId'] - 1 ] = $team;
}

$groups = array();
if ( ! empty( $tournament_data['groups'] ) ) {
	foreach ( $tournament_data['groups'] as $index => $group ) {
		$groups[ $index ] = $group['displayId'];
	}
}

// Process group matches (Preliminary Round).
$group_matches_by_date = array();
if ( ! empty( $tournament_data['groupMatches'] ) ) {
	foreach ( $tournament_data['groupMatches'] as $match ) {
		$date_time = $match['dateAndTime'];
		$match_date = substr( $date_time, 0, 10 );

		if ( ! empty( $date ) && $match_date !== $date ) {
			continue;
		}

		if ( ! isset( $group_matches_by_date[ $match_date ] ) ) {
			$group_matches_by_date[ $match_date ] = array();
		}

		$match['_stage'] = 'group';
		$group_matches_by_date[ $match_date ][] = $match;
	}
}

// Process final matches (Final Round).
$final_matches_by_date = array();
if ( ! empty( $tournament_data['finalMatches'] ) ) {
	foreach ( $tournament_data['finalMatches'] as $match ) {
		// Skip matches without teams assigned yet.
		if ( ! isset( $match['team1Id'] ) || ! isset( $match['team2Id'] ) ) {
			continue;
		}

		$date_time = $match['dateAndTime'];
		$match_date = substr( $date_time, 0, 10 );

		if ( ! empty( $date ) && $match_date !== $date ) {
			continue;
		}

		if ( ! isset( $final_matches_by_date[ $match_date ] ) ) {
			$final_matches_by_date[ $match_date ] = array();
		}

		$match['_stage'] = 'final';
		$final_matches_by_date[ $match_date ][] = $match;
	}
}

ksort( $group_matches_by_date );
ksort( $final_matches_by_date );

echo '<div class="matchday-match-schedule">';

// Display Preliminary Round (Group Matches).
if ( ! empty( $group_matches_by_date ) ) {
	echo '<div class="matchday-match-schedule__stage">';
	echo '<h3 class="matchday-match-schedule__stage-heading">' . esc_html__( 'Preliminary Round', 'matchday-blocks' ) . '</h3>';

	foreach ( $group_matches_by_date as $match_date => $matches ) {
		$manager->render_match_schedule_table( $matches, $match_date, $teams, $groups );
	}

	echo '</div>';
}

// Display Final Round (Knockout Matches).
if ( ! empty( $final_matches_by_date ) ) {
	echo '<div class="matchday-match-schedule__stage">';
	echo '<h3 class="matchday-match-schedule__stage-heading">' . esc_html__( 'Final Round', 'matchday-blocks' ) . '</h3>';

	foreach ( $final_matches_by_date as $match_date => $matches ) {
		$manager->render_match_schedule_table( $matches, $match_date, $teams, $groups );
	}

	echo '</div>';
}

echo '</div>';
