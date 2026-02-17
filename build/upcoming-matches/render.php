<?php
/**
 * Upcoming Matches Block Render Template
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

$limit = isset( $attributes['limit'] ) ? intval( $attributes['limit'] ) : 4;

$tournament_data_handler = \Matchday_Blocks\API\Tournament_Data::get_instance();
$tournament_data          = $tournament_data_handler->get_tournament_data();

if ( is_wp_error( $tournament_data ) ) {
	echo '<div class="matchday-error"><p>' . esc_html( $tournament_data->get_error_message() ) . '</p></div>';
	return;
}

if ( empty( $tournament_data['teams'] ) || empty( $tournament_data['groupMatches'] ) ) {
	echo '<div class="matchday-error"><p>' . esc_html__( 'Tournament data is incomplete or invalid.', 'matchday-blocks' ) . '</p></div>';
	return;
}

$teams = array();
foreach ( $tournament_data['teams'] as $team ) {
	$teams[ $team['displayId'] - 1 ] = $team;
}

$groups = array();
foreach ( $tournament_data['groups'] as $index => $group ) {
	$groups[ $index ] = $group['displayId'];
}

$tournament_name = isset( $tournament_data['name'] ) ? $tournament_data['name'] : '';

$future_matches = array();
foreach ( $tournament_data['groupMatches'] as $match ) {
	$score1 = isset( $match['score1'] ) ? $match['score1'] : '';
	$score2 = isset( $match['score2'] ) ? $match['score2'] : '';
	$has_result = ! empty( $score1 ) || ! empty( $score2 ) || $score1 === '0' || $score2 === '0';

	if ( ! $has_result ) {
		$future_matches[] = $match;
	}
}

usort( $future_matches, function ( $a, $b ) {
	return strcmp( $a['dateAndTime'], $b['dateAndTime'] );
} );

$future_matches = array_slice( $future_matches, 0, $limit );

echo '<div class="matchday-future-matches">';

foreach ( $future_matches as $match ) {
	$team1_id   = $match['team1Id'];
	$team2_id   = $match['team2Id'];
	$group_id   = $match['groupId'];
	$group_name = isset( $groups[ $group_id ] ) ? $groups[ $group_id ] : '';
	$date_time  = $match['dateAndTime'];
	$time       = substr( $date_time, 11, 5 );
	$date       = substr( $date_time, 0, 10 );

	$date_obj = \DateTime::createFromFormat( 'Y-m-d', $date );
	$formatted_date = $date_obj ? $date_obj->format( 'F j, Y' ) : $date;

	$team1 = isset( $teams[ $team1_id ] ) ? $teams[ $team1_id ] : null;
	$team2 = isset( $teams[ $team2_id ] ) ? $teams[ $team2_id ] : null;

	if ( ! $team1 || ! $team2 ) {
		continue;
	}

	$team1_name = $team1['name'];
	$team2_name = $team2['name'];
	$team1_logo = isset( $team1['logo']['lx95w'] ) ? $team1['logo']['lx95w'] : '';
	$team2_logo = isset( $team2['logo']['lx95w'] ) ? $team2['logo']['lx95w'] : '';

	echo '<div class="matchday-future-match-card">';
	echo '<div class="matchday-future-match-card__header">';
	echo '<div class="matchday-future-match-card__tournament">' . esc_html( $tournament_name ) . '</div>';
	if ( ! empty( $group_name ) ) {
		echo '<div class="matchday-future-match-card__group">Group ' . esc_html( $group_name ) . '</div>';
	}
	echo '</div>';
	echo '<div class="matchday-future-match-card__content">';
	echo '<div class="matchday-future-match-card__teams">';
	echo '<div class="matchday-future-match-card__team matchday-future-match-card__team--1">';
	if ( ! empty( $team1_logo ) ) {
		echo '<img src="' . esc_url( $team1_logo ) . '" alt="' . esc_attr( $team1_name ) . '" class="matchday-future-match-card__logo" />';
	}
	echo '<div class="matchday-future-match-card__team-name">' . esc_html( $team1_name ) . '</div>';
	echo '</div>';
	echo '<div class="matchday-future-match-card__vs">VS</div>';
	echo '<div class="matchday-future-match-card__team matchday-future-match-card__team--2">';
	if ( ! empty( $team2_logo ) ) {
		echo '<img src="' . esc_url( $team2_logo ) . '" alt="' . esc_attr( $team2_name ) . '" class="matchday-future-match-card__logo" />';
	}
	echo '<div class="matchday-future-match-card__team-name">' . esc_html( $team2_name ) . '</div>';
	echo '</div>';
	echo '</div>';
	echo '</div>';
	echo '<div class="matchday-future-match-card__footer">';
	echo '<div class="matchday-future-match-card__date">' . esc_html( $formatted_date ) . '</div>';
	echo '<div class="matchday-future-match-card__time">' . esc_html( $time ) . '</div>';
	echo '</div>';
	echo '</div>';
}

echo '</div>';
