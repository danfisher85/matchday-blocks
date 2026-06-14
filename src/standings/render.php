<?php
/**
 * Standings Block Render Template
 *
 * @package Matchday_Blocks
 * @since   1.0.0
 * @version 1.1.2
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
	$group = isset( $attributes['group'] ) ? $attributes['group'] : '';
	$show_final_standings = isset( $attributes['showFinalStandings'] ) ? $attributes['showFinalStandings'] : true;

	// Get tournament data.
	$tournament_data_handler = \Matchday_Blocks\API\Tournament_Data::get_instance();
	$tournament_data          = $tournament_data_handler->get_tournament_data();

	// Get manager instance for helper methods.
	$manager = \Matchday_Blocks\Blocks\Blocks_Manager::get_instance();

	// Check if data is valid.
	if ( is_wp_error( $tournament_data ) ) {
		echo '<div class="matchday-error">';
		echo '<p>' . esc_html( $tournament_data->get_error_message() ) . '</p>';
		echo '</div>';
		return;
	}

	// Show a graceful message when the tournament has standings hidden.
	$hide_ranks = isset( $tournament_data['hideRanks'] ) ? $tournament_data['hideRanks'] : 'none';
	if ( 'none' !== $hide_ranks ) {
		echo '<div class="matchday-error">';
		echo '<p>' . esc_html__( 'Standings are hidden for this tournament.', 'matchday-blocks' ) . '</p>';
		echo '</div>';
		return;
	}

	// Check if required data exists.
	if (
		empty( $tournament_data['participants'] ) ||
		empty( $tournament_data['groupRankTables'] )
	) {
		echo '<div class="matchday-error">';
		echo '<p>' . esc_html__( 'Tournament data is incomplete or invalid.', 'matchday-blocks' ) . '</p>';
		echo '</div>';
		return;
	}

	// Use participants keyed by their numeric ID for direct lookup.
	$teams = $tournament_data['participants'];

	// Display standings.
	echo '<div class="matchday-standings">';

	// Check if groups data exists.
	$has_groups = ! empty( $tournament_data['groups'] );

	// Loop through each group.
	foreach ( $tournament_data['groupRankTables'] as $group_index => $rank_table ) {
		// Get group name if groups exist.
		$group_display_id = '';
		$group_heading    = '';
		if ( $has_groups && isset( $tournament_data['groups'][ $group_index ] ) ) {
			$group_data       = $tournament_data['groups'][ $group_index ];
			$group_display_id = $group_data['displayId'];

			// Skip if specific group is requested and this is not it.
			if ( ! empty( $group ) && strtoupper( $group ) !== strtoupper( $group_display_id ) ) {
				continue;
			}

			if ( ! empty( $group_data['name'] ) ) {
				$group_heading = $group_data['name'] . ' (' . $group_display_id . ')';
			} else {
				/* translators: %s: group letter */
				$group_heading = sprintf( __( 'Group %s', 'matchday-blocks' ), $group_display_id );
			}
		}

		echo '<div class="matchday-standings__group">';
		if ( ! empty( $group_heading ) ) {
			$group_logo = isset( $group_data['logo']['lx95'] ) ? $group_data['logo']['lx95'] : '';
			echo '<div class="matchday-standings__group-heading">';
			if ( ! empty( $group_logo ) ) {
				echo '<img src="' . esc_url( $group_logo ) . '" alt="' . esc_attr( $group_heading ) . '" class="matchday-standings__group-logo">';
			}
			echo '<h3 class="matchday-standings__group-title">' . esc_html( $group_heading ) . '</h3>';
			echo '</div>';
		}
		echo '<div class="matchday-standings__table-wrapper">';
		echo '<table>';
		echo '<thead>';
		echo '<tr>';
		echo '<th class="matchday-table__pos">' . esc_html__( 'Pl', 'matchday-blocks' ) . '</th>';
		echo '<th class="matchday-table__participant">' . esc_html__( 'Participants', 'matchday-blocks' ) . '</th>';
		echo '<th>' . esc_html__( 'M', 'matchday-blocks' ) . '</th>';
		echo '<th>' . esc_html__( 'G', 'matchday-blocks' ) . '</th>';
		echo '<th>' . esc_html__( 'GD', 'matchday-blocks' ) . '</th>';
		echo '<th class="matchday-table__pts">' . esc_html__( 'Pts', 'matchday-blocks' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		// Loop through each team in the rank table.
		foreach ( $rank_table as $rank_entry ) {
			$team_id = $rank_entry['participant'];

			if ( ! isset( $teams[ $team_id ] ) ) {
				continue;
			}

			$team      = $teams[ $team_id ];
			$team_name = ! empty( $team['name'] ) ? $team['name'] : '–';
			$team_logo = isset( $team['logo']['lx95w'] ) ? $team['logo']['lx95w'] : '';

			$has_stats = isset( $rank_entry['rank'] );

			if ( $has_stats ) {
				$rank      = $rank_entry['rank'];
				$matches   = $rank_entry['gameCount'];
				$goals     = $rank_entry['ownGoalCount'] . ':' . $rank_entry['opponentGoalCount'];
				$goal_diff = $rank_entry['goalDifference'];
				$points    = $rank_entry['points'];

				$rank_display      = $manager->get_ordinal_suffix( $rank );
				$goal_diff_display = $goal_diff > 0 ? '+' . $goal_diff : $goal_diff;
			}

			echo '<tr>';
			echo '<td class="matchday-table__pos">' . esc_html( $has_stats ? $rank_display : '–' ) . '</td>';
			echo '<td class="matchday-table__participant">';
			echo '<div class="matchday-table__participant-inner">';
			if ( ! empty( $team_logo ) ) {
				echo '<img src="' . esc_url( $team_logo ) . '" alt="' . esc_attr( $team_name ) . '" width="24" height="24"> ';
			}
			echo esc_html( $team_name );
			echo '</div>';
			echo '</td>';
			echo '<td>' . esc_html( $has_stats ? $matches : '–' ) . '</td>';
			echo '<td>' . esc_html( $has_stats ? $goals : '–:–' ) . '</td>';
			echo '<td>' . esc_html( $has_stats ? $goal_diff_display : '–' ) . '</td>';
			echo '<td class="matchday-table__pts">' . esc_html( $has_stats ? $points : '–' ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';
		echo '</div>';
	}

	// Display final standings if available and enabled.
	if ( $show_final_standings && ! empty( $tournament_data['finalMatches'] ) && ! empty( $tournament_data['finalRankTable'] ) ) {
		$final_standings = $manager->get_final_standings_from_rank_table( $tournament_data['finalRankTable'], $tournament_data['finalMatches'], $teams );

		if ( ! empty( $final_standings ) ) {
			echo '<div class="matchday-standings__group">';
			echo '<h3 class="matchday-standings__group-heading">' . esc_html__( 'Final Standings', 'matchday-blocks' ) . '</h3>';
			echo '<div class="matchday-standings__table-wrapper">';
			echo '<table>';
			echo '<thead>';
			echo '<tr>';
			echo '<th class="matchday-table__pos">' . esc_html__( 'Pl', 'matchday-blocks' ) . '</th>';
			echo '<th class="matchday-table__participant">' . esc_html__( 'Participants', 'matchday-blocks' ) . '</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			foreach ( $final_standings as $rank => $team_id ) {
				// Skip if team doesn't exist in teams data.
				if ( ! isset( $teams[ $team_id ] ) ) {
					continue;
				}

				$team      = $teams[ $team_id ];
				$team_name = $team['name'];
				$team_logo = isset( $team['logo']['lx95w'] ) ? $team['logo']['lx95w'] : '';
				$rank_display = $manager->get_ordinal_suffix( $rank );

				echo '<tr>';
				echo '<td class="matchday-table__pos">' . esc_html( $rank_display ) . '</td>';
				echo '<td class="matchday-table__participant">';
				echo '<div class="matchday-table__participant-inner">';
				if ( ! empty( $team_logo ) ) {
					echo '<img src="' . esc_url( $team_logo ) . '" alt="' . esc_attr( $team_name ) . '" width="24" height="24"> ';
				}
				echo esc_html( $team_name );
				echo '</div>';
				echo '</td>';
				echo '</tr>';
			}

			echo '</tbody>';
			echo '</table>';
			echo '</div>';
			echo '</div>';
		}
	}

	echo '</div>';
} )( $attributes, $content, $block );
