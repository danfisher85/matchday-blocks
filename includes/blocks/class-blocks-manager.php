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
	 * Blocks are now registered using block.json metadata,
	 * which includes render callback references to render.php files.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_blocks() {
		// Register standings block.
		register_block_type( MATCHDAY_BLOCKS_PLUGIN_DIR . 'build/standings' );

		// Register match schedule block.
		register_block_type( MATCHDAY_BLOCKS_PLUGIN_DIR . 'build/match-schedule' );

		// Register latest matches block.
		register_block_type( MATCHDAY_BLOCKS_PLUGIN_DIR . 'build/latest-matches' );

		// Register upcoming matches block.
		register_block_type( MATCHDAY_BLOCKS_PLUGIN_DIR . 'build/upcoming-matches' );
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
	public function get_final_standings_from_rank_table( $rank_table, $final_matches, $teams ) {
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
	 * Render match schedule table for a specific date
	 *
	 * @since 1.0.0
	 * @param array  $matches    Matches for the date.
	 * @param string $match_date Date of the matches.
	 * @param array  $teams      Teams data.
	 * @param array  $groups     Groups data.
	 * @return void
	 */
	public function render_match_schedule_table( $matches, $match_date, $teams, $groups ) {
		$date_obj = \DateTime::createFromFormat( 'Y-m-d', $match_date );
		$date_heading = $date_obj ? $date_obj->format( 'l, F j, Y' ) : $match_date;

		// Detect if this is a final stage table by checking the first match.
		$is_final_stage = false;
		if ( ! empty( $matches ) && isset( $matches[0]['_stage'] ) && 'final' === $matches[0]['_stage'] ) {
			$is_final_stage = true;
		}

		// Check if groups exist.
		$has_groups = ! empty( $groups );

		echo '<div class="matchday-match-schedule__date">';
		echo '<h4 class="matchday-match-schedule__date-heading">' . esc_html( $date_heading ) . '</h4>';
		echo '<div class="matchday-match-schedule__table-wrapper">';
		echo '<table><thead><tr>';
		echo '<th>№</th><th>' . esc_html__( 'Start', 'matchday-blocks' ) . '</th>';
		if ( ! $is_final_stage && $has_groups ) {
			echo '<th>' . esc_html__( 'Gr', 'matchday-blocks' ) . '</th>';
		}
		echo '<th colspan="3">' . esc_html__( 'Match', 'matchday-blocks' ) . '</th><th>' . esc_html__( 'Result', 'matchday-blocks' ) . '</th>';
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
				// Colspan: № + Start + (Gr if shown) + Match (3 cols) + Result = 6 or 5.
				$title_colspan = ( ! $is_final_stage && $has_groups ) ? '6' : '5';
				echo '<tr class="matchday-match-title-row">';
				echo '<td colspan="' . esc_attr( $title_colspan ) . '" class="matchday-match-title">' . esc_html( $match_title ) . '</td>';
				echo '</tr>';
			}

			echo '<tr>';
			echo '<td>' . esc_html( $match_number ) . '</td>';
			echo '<td>' . esc_html( $time ) . '</td>';
			if ( ! $is_final_stage && $has_groups ) {
				echo '<td>';
				if ( ! empty( $group_name ) ) {
					echo '<span class="matchday-group-badge">' . esc_html( $group_name ) . '</span>';
				}
				echo '</td>';
			}
			echo '<td class="matchday-table__participant matchday-table__participant--team1">';
			echo '<div class="matchday-table__participant-inner">';
			if ( ! empty( $team1_logo ) ) {
				echo '<img src="' . esc_url( $team1_logo ) . '" alt="' . esc_attr( $team1_name ) . '" width="24" height="24"> ';
			}
			echo '<div class="matchday-table__participant-info">';
			echo '<div class="matchday-table__participant-name">';
			echo esc_html( $team1_name );
			echo '</div>';
			if ( ! empty( $team1_source ) ) {
				echo '<div class="matchday-table__participant-source">' . esc_html( $team1_source ) . '</div>';
			}
			echo '</div>';
			echo '</div></td>';
			echo '<td class="matchday-match-vs">:</td>';
			echo '<td class="matchday-table__participant matchday-table__participant--team2">';
			echo '<div class="matchday-table__participant-inner">';
			if ( ! empty( $team2_logo ) ) {
				echo '<img src="' . esc_url( $team2_logo ) . '" alt="' . esc_attr( $team2_name ) . '" width="24" height="24"> ';
			}
			echo '<div class="matchday-table__participant-info">';
			echo '<div class="matchday-table__participant-name">';
			echo esc_html( $team2_name );
			echo '</div>';
			if ( ! empty( $team2_source ) ) {
				echo '<div class="matchday-table__participant-source">' . esc_html( $team2_source ) . '</div>';
			}
			echo '</div>';
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
	public function get_final_match_title( $mode_mapping ) {
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
	public function get_team_source_description( $source_team, $groups ) {
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

			$rank_label = isset( $rank_labels[ $rank ] ) ? $rank_labels[ $rank ] : $rank . __( 'th', 'matchday-blocks' );

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
	 * Get ordinal suffix for a number
	 *
	 * @since 1.0.0
	 * @param int $number The number to get the suffix for.
	 * @return string The number with ordinal suffix (e.g., "1st", "2nd", "3rd").
	 */
	public function get_ordinal_suffix( $number ) {
		$number = (int) $number;

		if ( $number % 100 >= 11 && $number % 100 <= 13 ) {
			/* translators: ordinal number suffix for 11th-13th */
			return $number . __( 'th', 'matchday-blocks' );
		}

		switch ( $number % 10 ) {
			case 1:
				/* translators: ordinal number suffix for 1st */
				return $number . __( 'st', 'matchday-blocks' );
			case 2:
				/* translators: ordinal number suffix for 2nd */
				return $number . __( 'nd', 'matchday-blocks' );
			case 3:
				/* translators: ordinal number suffix for 3rd */
				return $number . __( 'rd', 'matchday-blocks' );
			default:
				/* translators: ordinal number suffix for 4th and above */
				return $number . __( 'th', 'matchday-blocks' );
		}
	}
}
