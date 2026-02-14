<?php
/**
 * Blocks Registration and Management
 *
 * Handles registration of Gutenberg blocks.
 *
 * @package   Matchday_Blocks
 * @since     1.0.0
 */

namespace Matchday_Blocks\Blocks;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Blocks_Manager class
 *
 * Manages block registration and rendering.
 *
 * @since 1.0.0
 */
class Blocks_Manager {

	/**
	 * Instance of this class
	 *
	 * @since 1.0.0
	 * @var Blocks_Manager|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @since 1.0.0
	 * @return Blocks_Manager
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
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_block_styles' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_styles' ) );
	}

	/**
	 * Enqueue block styles for frontend
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_block_styles() {
		$css_file = MATCHDAY_BLOCKS_PLUGIN_DIR . 'assets/css/blocks.css';

		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'matchday-blocks-styles',
				MATCHDAY_BLOCKS_PLUGIN_URL . 'assets/css/blocks.css',
				array(),
				filemtime( $css_file )
			);
		}
	}

	/**
	 * Enqueue block styles for editor
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_editor_styles() {
		$css_file = MATCHDAY_BLOCKS_PLUGIN_DIR . 'assets/css/blocks.css';

		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'matchday-blocks-editor-styles',
				MATCHDAY_BLOCKS_PLUGIN_URL . 'assets/css/blocks.css',
				array(),
				filemtime( $css_file )
			);
		}
	}

	/**
	 * Register all blocks
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_blocks() {
		// Register standings block.
		register_block_type(
			MATCHDAY_BLOCKS_PLUGIN_DIR . 'build/standings',
			array(
				'render_callback' => array( $this, 'render_standings_block' ),
			)
		);

		// Register match schedule block.
		register_block_type(
			MATCHDAY_BLOCKS_PLUGIN_DIR . 'build/match-schedule',
			array(
				'render_callback' => array( $this, 'render_match_schedule_block' ),
			)
		);

		// Register latest matches block.
		register_block_type(
			MATCHDAY_BLOCKS_PLUGIN_DIR . 'build/latest-matches',
			array(
				'render_callback' => array( $this, 'render_latest_matches_block' ),
			)
		);

		// Register upcoming matches block.
		register_block_type(
			MATCHDAY_BLOCKS_PLUGIN_DIR . 'build/upcoming-matches',
			array(
				'render_callback' => array( $this, 'render_upcoming_matches_block' ),
			)
		);
	}


	/**
	 * Render standings block
	 *
	 * @since 1.0.0
	 * @param array $attributes Block attributes.
	 * @return string Block HTML.
	 */
	public function render_standings_block( $attributes ) {
		$group = isset( $attributes['group'] ) ? $attributes['group'] : '';

		// Get tournament data.
		$tournament_data_handler = \Matchday_Blocks\API\Tournament_Data::get_instance();
		$tournament_data          = $tournament_data_handler->get_tournament_data();

		// Start output buffering.
		ob_start();

		// Check if data is valid.
		if ( is_wp_error( $tournament_data ) ) {
			echo '<div class="matchday-error">';
			echo '<p>' . esc_html( $tournament_data->get_error_message() ) . '</p>';
			echo '</div>';
			return ob_get_clean();
		}

		// Check if required data exists.
		if (
			empty( $tournament_data['teams'] ) ||
			empty( $tournament_data['groupRankTables'] ) ||
			empty( $tournament_data['groups'] )
		) {
			echo '<div class="matchday-error">';
			echo '<p>' . esc_html__( 'Tournament data is incomplete or invalid.', 'matchday-blocks' ) . '</p>';
			echo '</div>';
			return ob_get_clean();
		}

		// Create a teams lookup array.
		$teams = array();
		foreach ( $tournament_data['teams'] as $team ) {
			$teams[ $team['displayId'] - 1 ] = $team;
		}

		// Display standings.
		echo '<div class="matchday-standings">';

		// Loop through each group.
		foreach ( $tournament_data['groups'] as $group_index => $group_data ) {
			$group_name = $group_data['displayId'];

			// Skip if specific group is requested and this is not it.
			if ( ! empty( $group ) && strtoupper( $group ) !== strtoupper( $group_name ) ) {
				continue;
			}

			// Get the rank table for this group.
			if ( ! isset( $tournament_data['groupRankTables'][ $group_index ] ) ) {
				continue;
			}

			$rank_table = $tournament_data['groupRankTables'][ $group_index ];

			echo '<div class="matchday-standings__group">';
			echo '<h3 class="matchday-standings__group-heading">Group ' . esc_html( $group_name ) . '</h3>';
			echo '<div class="matchday-standings__table-wrapper">';
			echo '<table>';
			echo '<thead>';
			echo '<tr>';
			echo '<th class="matchday-table__pos">Pl</th>';
			echo '<th class="matchday-table__participant">Participants</th>';
			echo '<th>M</th>';
			echo '<th>G</th>';
			echo '<th>GD</th>';
			echo '<th class="matchday-table__pts">Pts</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			// Loop through each team in the rank table.
			foreach ( $rank_table as $rank_entry ) {
				$team_id    = $rank_entry['teamId'];
				$rank       = $rank_entry['rank'];
				$matches    = $rank_entry['numMatches'];
				$goals      = $rank_entry['ownGoals'] . ':' . $rank_entry['otherGoals'];
				$goal_diff  = $rank_entry['goalDiff'];
				$points     = $rank_entry['points'];

				if ( ! isset( $teams[ $team_id ] ) ) {
					continue;
				}

				$team      = $teams[ $team_id ];
				$team_name = $team['name'];
				$team_logo = isset( $team['logo']['lx32w'] ) ? $team['logo']['lx32w'] : '';

				$rank_display = $this->get_ordinal_suffix( $rank );
				$goal_diff_display = $goal_diff;
				if ( $goal_diff > 0 ) {
					$goal_diff_display = '+' . $goal_diff;
				}

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
				echo '<td>' . esc_html( $matches ) . '</td>';
				echo '<td>' . esc_html( $goals ) . '</td>';
				echo '<td>' . esc_html( $goal_diff_display ) . '</td>';
				echo '<td class="matchday-table__pts">' . esc_html( $points ) . '</td>';
				echo '</tr>';
			}

			echo '</tbody>';
			echo '</table>';
			echo '</div>';
			echo '</div>';
		}

		// Display final standings if available.
		if ( ! empty( $tournament_data['finalMatches'] ) && ! empty( $tournament_data['finalRankTable'] ) ) {
			$final_standings = $this->get_final_standings_from_rank_table( $tournament_data['finalRankTable'], $tournament_data['finalMatches'], $teams );

			if ( ! empty( $final_standings ) ) {
				echo '<div class="matchday-standings__group">';
				echo '<h3 class="matchday-standings__group-heading">' . esc_html__( 'Final Standings', 'matchday-blocks' ) . '</h3>';
				echo '<div class="matchday-standings__table-wrapper">';
				echo '<table>';
				echo '<thead>';
				echo '<tr>';
				echo '<th class="matchday-table__pos">Pl</th>';
				echo '<th class="matchday-table__participant">Participants</th>';
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
					$team_logo = isset( $team['logo']['lx32w'] ) ? $team['logo']['lx32w'] : '';
					$rank_display = $this->get_ordinal_suffix( $rank );

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

		return ob_get_clean();
	}

	/**
	 * Get final standings from rank table
	 *
	 * @since 1.0.0
	 * @param array $rank_table    Final rank table data.
	 * @param array $final_matches Final matches data (unused, kept for compatibility).
	 * @param array $teams         Teams data (unused, kept for compatibility).
	 * @return array Final standings array with rank => team_id.
	 */
	private function get_final_standings_from_rank_table( $rank_table, $final_matches, $teams ) {
		$standings = array();

		// Process rank table entries directly from API.
		if ( is_array( $rank_table ) ) {
			foreach ( $rank_table as $entry ) {
				if ( isset( $entry['rank'] ) && isset( $entry['teamId'] ) ) {
					$rank = intval( $entry['rank'] );
					$team_id = $entry['teamId'];
					$standings[ $rank ] = $team_id;
				}
			}
		}

		// Sort by rank to ensure proper order.
		ksort( $standings );

		return $standings;
	}

	/**
	 * Calculate final standings from final matches
	 *
	 * @since 1.0.0
	 * @param array $final_matches Final matches data.
	 * @param array $teams         Teams data.
	 * @return array Final standings array with rank => team_id.
	 */
	private function calculate_final_standings( $final_matches, $teams ) {
		$standings = array();

		// Initialize standings positions (1-8).
		for ( $i = 1; $i <= 8; $i++ ) {
			$standings[ $i ] = null;
		}

		// Process each final match.
		foreach ( $final_matches as $match ) {
			// Skip matches without results or teams.
			if ( ! isset( $match['team1Id'] ) || ! isset( $match['team2Id'] ) ) {
				continue;
			}

			$score1 = isset( $match['score1'] ) ? $match['score1'] : '';
			$score2 = isset( $match['score2'] ) ? $match['score2'] : '';

			// Skip if no score is set.
			if ( '' === $score1 || '' === $score2 ) {
				continue;
			}

			$team1_id = $match['team1Id'];
			$team2_id = $match['team2Id'];

			// Convert scores to integers for comparison.
			$score1_int = intval( $score1 );
			$score2_int = intval( $score2 );

			// Determine winner and loser.
			$winner_id = null;
			$loser_id  = null;

			if ( $score1_int > $score2_int ) {
				$winner_id = $team1_id;
				$loser_id  = $team2_id;
			} elseif ( $score2_int > $score1_int ) {
				$winner_id = $team2_id;
				$loser_id  = $team1_id;
			}

			// Get modeMapping to determine which placement match this is.
			if ( isset( $match['modeMapping'] ) ) {
				$round = $match['modeMapping']['round'];
				$match_num = $match['modeMapping']['match'];

				// Round 1 matches determine final placements.
				if ( 1 === $round ) {
					if ( 1 === $match_num ) {
						// Final: 1st vs 2nd place.
						if ( $winner_id ) {
							$standings[1] = $winner_id;
							$standings[2] = $loser_id;
						}
					} elseif ( 2 === $match_num ) {
						// Match for 3rd place: 3rd vs 4th place.
						if ( $winner_id ) {
							$standings[3] = $winner_id;
							$standings[4] = $loser_id;
						}
					} elseif ( 3 === $match_num ) {
						// Match for 5th place: 5th vs 6th place.
						if ( $winner_id ) {
							$standings[5] = $winner_id;
							$standings[6] = $loser_id;
						}
					} elseif ( 4 === $match_num ) {
						// Match for 7th place: 7th vs 8th place.
						if ( $winner_id ) {
							$standings[7] = $winner_id;
							$standings[8] = $loser_id;
						}
					}
				}
			}
		}

		return $standings;
	}

	/**
	 * Render match schedule block
	 *
	 * @since 1.0.0
	 * @param array $attributes Block attributes.
	 * @return string Block HTML.
	 */
	public function render_match_schedule_block( $attributes ) {
		$date = isset( $attributes['date'] ) ? $attributes['date'] : '';

		// Get tournament data.
		$tournament_data_handler = \Matchday_Blocks\API\Tournament_Data::get_instance();
		$tournament_data          = $tournament_data_handler->get_tournament_data();

		ob_start();

		if ( is_wp_error( $tournament_data ) ) {
			echo '<div class="matchday-error"><p>' . esc_html( $tournament_data->get_error_message() ) . '</p></div>';
			return ob_get_clean();
		}

		if ( empty( $tournament_data['teams'] ) ) {
			echo '<div class="matchday-error"><p>' . esc_html__( 'Tournament data is incomplete or invalid.', 'matchday-blocks' ) . '</p></div>';
			return ob_get_clean();
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
				$this->render_match_schedule_table( $matches, $match_date, $teams, $groups );
			}

			echo '</div>';
		}

		// Display Final Round (Knockout Matches).
		if ( ! empty( $final_matches_by_date ) ) {
			echo '<div class="matchday-match-schedule__stage">';
			echo '<h3 class="matchday-match-schedule__stage-heading">' . esc_html__( 'Final Round', 'matchday-blocks' ) . '</h3>';

			foreach ( $final_matches_by_date as $match_date => $matches ) {
				$this->render_match_schedule_table( $matches, $match_date, $teams, $groups );
			}

			echo '</div>';
		}

		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * Render match schedule table for a specific date
	 *
	 * @since 1.0.0
	 * @param array  $matches    Matches for the date.
	 * @param string $match_date Date of the matches.
	 * @param array  $teams      Teams data.
	 * @param array  $groups     Groups data.
	 * @return void
	 */
	private function render_match_schedule_table( $matches, $match_date, $teams, $groups ) {
		$date_obj = \DateTime::createFromFormat( 'Y-m-d', $match_date );
		$date_heading = $date_obj ? $date_obj->format( 'l, F j, Y' ) : $match_date;

		// Detect if this is a final stage table by checking the first match.
		$is_final_stage = false;
		if ( ! empty( $matches ) && isset( $matches[0]['_stage'] ) && 'final' === $matches[0]['_stage'] ) {
			$is_final_stage = true;
		}

		echo '<div class="matchday-match-schedule__date">';
		echo '<h4 class="matchday-match-schedule__date-heading">' . esc_html( $date_heading ) . '</h4>';
		echo '<div class="matchday-match-schedule__table-wrapper">';
		echo '<table><thead><tr>';
		echo '<th>№</th><th>Start</th>';
		if ( ! $is_final_stage ) {
			echo '<th>Gr</th>';
		}
		echo '<th colspan="3">Match</th><th>Result</th>';
		echo '</tr></thead><tbody>';

		foreach ( $matches as $match ) {
			$time         = substr( $match['dateAndTime'], 11, 5 );
			$match_number = $match['displayId'];
			$stage        = isset( $match['_stage'] ) ? $match['_stage'] : 'group';
			$group_name   = '';

			if ( $stage === 'group' && isset( $match['groupId'] ) ) {
				$group_id = $match['groupId'];
				$group_name = isset( $groups[ $group_id ] ) ? $groups[ $group_id ] : '';
			}

			$team1_id = $match['team1Id'];
			$team2_id = $match['team2Id'];
			$score1   = isset( $match['score1'] ) ? $match['score1'] : '';
			$score2   = isset( $match['score2'] ) ? $match['score2'] : '';

			$team1 = isset( $teams[ $team1_id ] ) ? $teams[ $team1_id ] : null;
			$team2 = isset( $teams[ $team2_id ] ) ? $teams[ $team2_id ] : null;

			if ( ! $team1 || ! $team2 ) {
				continue;
			}

			$team1_name = $team1['name'];
			$team2_name = $team2['name'];
			$team1_logo = isset( $team1['logo']['lx32w'] ) ? $team1['logo']['lx32w'] : '';
			$team2_logo = isset( $team2['logo']['lx32w'] ) ? $team2['logo']['lx32w'] : '';

			$has_result = ! empty( $score1 ) || ! empty( $score2 ) || $score1 === '0' || $score2 === '0';

			// Get match title and source descriptions for final matches.
			$match_title = '';
			$team1_source = '';
			$team2_source = '';
			if ( $stage === 'final' && isset( $match['modeMapping'] ) ) {
				$match_title = $this->get_final_match_title( $match['modeMapping'] );
				if ( isset( $match['sourceTeam1'] ) ) {
					$team1_source = $this->get_team_source_description( $match['sourceTeam1'], $groups );
				}
				if ( isset( $match['sourceTeam2'] ) ) {
					$team2_source = $this->get_team_source_description( $match['sourceTeam2'], $groups );
				}
			}

			// Display match title row for final matches.
			if ( ! empty( $match_title ) ) {
				echo '<tr class="matchday-match-title-row">';
				echo '<td colspan="7" class="matchday-match-title">' . esc_html( $match_title ) . '</td>';
				echo '</tr>';
			}

			echo '<tr>';
			echo '<td>' . esc_html( $match_number ) . '</td>';
			echo '<td>' . esc_html( $time ) . '</td>';
			if ( ! $is_final_stage ) {
				echo '<td>';
				if ( ! empty( $group_name ) ) {
					echo '<span class="matchday-group-badge">' . esc_html( $group_name ) . '</span>';
				}
				echo '</td>';
			}
			echo '<td class="matchday-table__participant matchday-table__participant--team1">';
			if ( ! empty( $team1_source ) ) {
				echo '<div class="matchday-table__participant-source">' . esc_html( $team1_source ) . '</div>';
			}
			echo '<div class="matchday-table__participant-inner">';
			if ( ! empty( $team1_logo ) ) {
				echo '<img src="' . esc_url( $team1_logo ) . '" alt="' . esc_attr( $team1_name ) . '" width="24" height="24"> ';
			}
			echo esc_html( $team1_name );
			echo '</div></td>';
			echo '<td class="matchday-match-vs">:</td>';
			echo '<td class="matchday-table__participant matchday-table__participant--team2">';
			if ( ! empty( $team2_source ) ) {
				echo '<div class="matchday-table__participant-source">' . esc_html( $team2_source ) . '</div>';
			}
			echo '<div class="matchday-table__participant-inner">';
			if ( ! empty( $team2_logo ) ) {
				echo '<img src="' . esc_url( $team2_logo ) . '" alt="' . esc_attr( $team2_name ) . '" width="24" height="24"> ';
			}
			echo esc_html( $team2_name );
			echo '</div></td>';
			echo '<td class="matchday-match-final-score">';
			if ( $has_result ) {
				echo esc_html( $score1 ) . ':' . esc_html( $score2 );
			} else {
				echo '-:-';
			}
			echo '</td></tr>';
		}

		echo '</tbody></table></div></div>';
	}

	/**
	 * Get final match title based on mode mapping
	 *
	 * @since 1.0.0
	 * @param array $mode_mapping Mode mapping data.
	 * @return string Match title.
	 */
	private function get_final_match_title( $mode_mapping ) {
		if ( ! isset( $mode_mapping['round'] ) || ! isset( $mode_mapping['match'] ) ) {
			return '';
		}

		$round = $mode_mapping['round'];
		$match = $mode_mapping['match'];

		// Determine match title based on round and match number.
		if ( 1 === $round ) {
			if ( 1 === $match ) {
				return __( 'Final', 'matchday-blocks' );
			} elseif ( 2 === $match ) {
				return __( 'Match for 3rd Place', 'matchday-blocks' );
			} elseif ( 3 === $match ) {
				return __( 'Match for 5th Place', 'matchday-blocks' );
			} elseif ( 4 === $match ) {
				return __( 'Match for 7th Place', 'matchday-blocks' );
			}
		} elseif ( 2 === $round ) {
			if ( 1 === $match ) {
				return __( '1st Semifinal', 'matchday-blocks' );
			} elseif ( 2 === $match ) {
				return __( '2nd Semifinal', 'matchday-blocks' );
			}
		} elseif ( 3 === $round ) {
			if ( 1 === $match ) {
				return __( '1st Quarterfinal', 'matchday-blocks' );
			} elseif ( 2 === $match ) {
				return __( '2nd Quarterfinal', 'matchday-blocks' );
			} elseif ( 3 === $match ) {
				return __( '3rd Quarterfinal', 'matchday-blocks' );
			} elseif ( 4 === $match ) {
				return __( '4th Quarterfinal', 'matchday-blocks' );
			}
		}

		return '';
	}

	/**
	 * Get team source description
	 *
	 * @since 1.0.0
	 * @param array $source_team Source team data.
	 * @param array $groups      Groups data.
	 * @return string Source description.
	 */
	private function get_team_source_description( $source_team, $groups ) {
		if ( ! isset( $source_team['type'] ) ) {
			return '';
		}

		$type = $source_team['type'];

		if ( 'group' === $type && isset( $source_team['group'] ) && isset( $source_team['rank'] ) ) {
			$group_index = $source_team['group'];
			$rank = $source_team['rank'];
			$group_name = isset( $groups[ $group_index ] ) ? $groups[ $group_index ] : ( $group_index + 1 );

			$rank_labels = array(
				1 => __( '1st', 'matchday-blocks' ),
				2 => __( '2nd', 'matchday-blocks' ),
				3 => __( '3rd', 'matchday-blocks' ),
				4 => __( '4th', 'matchday-blocks' ),
			);

			$rank_label = isset( $rank_labels[ $rank ] ) ? $rank_labels[ $rank ] : $rank . 'th';

			/* translators: 1: rank label, 2: group name */
			return sprintf( __( '%1$s Group %2$s', 'matchday-blocks' ), $rank_label, $group_name );
		} elseif ( 'knockout' === $type && isset( $source_team['round'] ) && isset( $source_team['match'] ) && isset( $source_team['rank'] ) ) {
			$round = $source_team['round'];
			$match = $source_team['match'];
			$rank = $source_team['rank'];

			$match_name = '';
			if ( 2 === $round ) {
				$match_name = ( 1 === $match ) ? __( '1st Semifinal', 'matchday-blocks' ) : __( '2nd Semifinal', 'matchday-blocks' );
			} elseif ( 3 === $round ) {
				$quarterfinal_labels = array(
					1 => __( '1st Quarterfinal', 'matchday-blocks' ),
					2 => __( '2nd Quarterfinal', 'matchday-blocks' ),
					3 => __( '3rd Quarterfinal', 'matchday-blocks' ),
					4 => __( '4th Quarterfinal', 'matchday-blocks' ),
				);
				$match_name = isset( $quarterfinal_labels[ $match ] ) ? $quarterfinal_labels[ $match ] : '';
			}

			if ( ! empty( $match_name ) ) {
				$position_label = ( 1 === $rank ) ? __( 'Winner', 'matchday-blocks' ) : __( 'Loser', 'matchday-blocks' );
				/* translators: 1: position label (Winner/Loser), 2: match name */
				return sprintf( __( '%1$s %2$s', 'matchday-blocks' ), $position_label, $match_name );
			}
		}

		return '';
	}

	/**
	 * Render latest matches block
	 *
	 * @since 1.0.0
	 * @param array $attributes Block attributes.
	 * @return string Block HTML.
	 */
	public function render_latest_matches_block( $attributes ) {
		$limit = isset( $attributes['limit'] ) ? intval( $attributes['limit'] ) : 4;

		$tournament_data_handler = \Matchday_Blocks\API\Tournament_Data::get_instance();
		$tournament_data          = $tournament_data_handler->get_tournament_data();

		ob_start();

		if ( is_wp_error( $tournament_data ) ) {
			echo '<div class="matchday-error"><p>' . esc_html( $tournament_data->get_error_message() ) . '</p></div>';
			return ob_get_clean();
		}

		if ( empty( $tournament_data['teams'] ) || empty( $tournament_data['groupMatches'] ) ) {
			echo '<div class="matchday-error"><p>' . esc_html__( 'Tournament data is incomplete or invalid.', 'matchday-blocks' ) . '</p></div>';
			return ob_get_clean();
		}

		$teams = array();
		foreach ( $tournament_data['teams'] as $team ) {
			$teams[ $team['displayId'] - 1 ] = $team;
		}

		$tournament_name = isset( $tournament_data['name'] ) ? $tournament_data['name'] : '';

		$finished_matches = array();
		foreach ( $tournament_data['groupMatches'] as $match ) {
			$score1 = isset( $match['score1'] ) ? $match['score1'] : '';
			$score2 = isset( $match['score2'] ) ? $match['score2'] : '';
			$has_result = ! empty( $score1 ) || ! empty( $score2 ) || $score1 === '0' || $score2 === '0';

			if ( $has_result ) {
				$finished_matches[] = $match;
			}
		}

		usort( $finished_matches, function ( $a, $b ) {
			return strcmp( $b['dateAndTime'], $a['dateAndTime'] );
		} );

		$finished_matches = array_slice( $finished_matches, 0, $limit );

		echo '<div class="matchday-latest-matches">';

		foreach ( $finished_matches as $match ) {
			$team1_id = $match['team1Id'];
			$team2_id = $match['team2Id'];
			$score1   = isset( $match['score1'] ) ? $match['score1'] : '0';
			$score2   = isset( $match['score2'] ) ? $match['score2'] : '0';
			$time     = substr( $match['dateAndTime'], 11, 5 );

			$team1 = isset( $teams[ $team1_id ] ) ? $teams[ $team1_id ] : null;
			$team2 = isset( $teams[ $team2_id ] ) ? $teams[ $team2_id ] : null;

			if ( ! $team1 || ! $team2 ) {
				continue;
			}

			$team1_name = $team1['name'];
			$team2_name = $team2['name'];
			$team1_logo = isset( $team1['logo']['lx95w'] ) ? $team1['logo']['lx95w'] : '';
			$team2_logo = isset( $team2['logo']['lx95w'] ) ? $team2['logo']['lx95w'] : '';

			echo '<div class="matchday-match-card">';
			echo '<div class="matchday-match-card__content">';
			echo '<div class="matchday-match-card__team matchday-match-card__team--1">';
			echo '<div class="matchday-match-card__team-info">';
			if ( ! empty( $team1_logo ) ) {
				echo '<img src="' . esc_url( $team1_logo ) . '" alt="' . esc_attr( $team1_name ) . '" class="matchday-match-card__logo" />';
			}
			echo '<div class="matchday-match-card__team-name">' . esc_html( $team1_name ) . '</div>';
			echo '</div>';
			echo '<div class="matchday-match-card__score">' . esc_html( $score1 ) . '</div>';
			echo '</div>';
			echo '<div class="matchday-match-card__vs">VS</div>';
			echo '<div class="matchday-match-card__team matchday-match-card__team--2">';
			echo '<div class="matchday-match-card__team-info">';
			if ( ! empty( $team2_logo ) ) {
				echo '<img src="' . esc_url( $team2_logo ) . '" alt="' . esc_attr( $team2_name ) . '" class="matchday-match-card__logo" />';
			}
			echo '<div class="matchday-match-card__team-name">' . esc_html( $team2_name ) . '</div>';
			echo '</div>';
			echo '<div class="matchday-match-card__score">' . esc_html( $score2 ) . '</div>';
			echo '</div>';
			echo '</div>';
			echo '<div class="matchday-match-card__footer">';
			echo '<div class="matchday-match-card__tournament">' . esc_html( $tournament_name ) . '</div>';
			echo '<div class="matchday-match-card__time">' . esc_html( $time ) . '</div>';
			echo '</div>';
			echo '</div>';
		}

		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * Render upcoming matches block
	 *
	 * @since 1.0.0
	 * @param array $attributes Block attributes.
	 * @return string Block HTML.
	 */
	public function render_upcoming_matches_block( $attributes ) {
		$limit = isset( $attributes['limit'] ) ? intval( $attributes['limit'] ) : 4;

		$tournament_data_handler = \Matchday_Blocks\API\Tournament_Data::get_instance();
		$tournament_data          = $tournament_data_handler->get_tournament_data();

		ob_start();

		if ( is_wp_error( $tournament_data ) ) {
			echo '<div class="matchday-error"><p>' . esc_html( $tournament_data->get_error_message() ) . '</p></div>';
			return ob_get_clean();
		}

		if ( empty( $tournament_data['teams'] ) || empty( $tournament_data['groupMatches'] ) ) {
			echo '<div class="matchday-error"><p>' . esc_html__( 'Tournament data is incomplete or invalid.', 'matchday-blocks' ) . '</p></div>';
			return ob_get_clean();
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

		return ob_get_clean();
	}


	/**
	 * Get ordinal suffix for a number
	 *
	 * @since 1.0.0
	 * @param int $number The number to get the suffix for.
	 * @return string The number with ordinal suffix (e.g., "1st", "2nd", "3rd").
	 */
	private function get_ordinal_suffix( $number ) {
		$number = (int) $number;

		if ( $number % 100 >= 11 && $number % 100 <= 13 ) {
			return $number . 'th';
		}

		switch ( $number % 10 ) {
			case 1:
				return $number . 'st';
			case 2:
				return $number . 'nd';
			case 3:
				return $number . 'rd';
			default:
				return $number . 'th';
		}
	}
}
