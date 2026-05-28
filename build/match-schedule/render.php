<?php
/**
 * Match Schedule Block Render Template
 *
 * @package Matchday_Blocks
 * @since   1.0.0
 * @version 1.1.0
 *
 * @var array $attributes Block attributes.
 * @var string $content Block content.
 * @var WP_Block $block Block instance.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

( function ( $attributes, $content, $block ) {
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

	if ( empty( $tournament_data['participants'] ) ) {
		echo '<div class="matchday-error"><p>' . esc_html__( 'Tournament data is incomplete or invalid.', 'matchday-blocks' ) . '</p></div>';
		return;
	}

	$teams = $tournament_data['participants'];

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
			if ( ! isset( $match['homeParticipant'] ) || ! isset( $match['awayParticipant'] ) ) {
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

	// Build date → round number map from all group match dates (unfiltered).
	$all_group_dates = array();
	if ( ! empty( $tournament_data['groupMatches'] ) ) {
		foreach ( $tournament_data['groupMatches'] as $m ) {
			$all_group_dates[] = substr( $m['dateAndTime'], 0, 10 );
		}
	}
	$all_group_dates = array_unique( $all_group_dates );
	sort( $all_group_dates );
	$date_to_round = array();
	foreach ( $all_group_dates as $round_index => $round_date ) {
		$date_to_round[ $round_date ] = $round_index + 1;
	}

	echo '<div class="matchday-match-schedule">';

	$has_final_round = ! empty( $final_matches_by_date );

	// Display Preliminary Round (Group Matches).
	if ( ! empty( $group_matches_by_date ) ) {
		echo '<div class="matchday-match-schedule__stage">';
		if ( $has_final_round ) {
			echo '<h3 class="matchday-match-schedule__stage-heading">' . esc_html__( 'Preliminary Round', 'matchday-blocks' ) . '</h3>';
		}

		foreach ( $group_matches_by_date as $match_date => $matches ) {
			$round = isset( $date_to_round[ $match_date ] ) ? $date_to_round[ $match_date ] : null;
			$manager->render_match_schedule_table( $matches, $match_date, $teams, $groups, $round );
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
} )( $attributes, $content, $block );
