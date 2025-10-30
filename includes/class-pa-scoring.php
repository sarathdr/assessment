<?php
/**
 * Scoring
 *
 * @package           PersonalityAssessment
 * @author            Jules
 * @copyright         2024 Jules
 * @license           GPL-3.0-or-later
 *
 * @wordpress-plugin
 */

namespace PA;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The scoring functionality of the plugin.
 */
class Scoring {

	/**
	 * Calculate the score for a quiz.
	 */
	public static function calculate_score( $quiz_id, $answers ) {
		global $wpdb;

		$score_total = 0;
		$label_totals = array();
		$raw_payload = array();

		foreach ( $answers as $question_id => $answer_ids ) {
			if ( ! is_array( $answer_ids ) ) {
				$answer_ids = array( $answer_ids );
			}

			foreach ( $answer_ids as $answer_id ) {
				$answer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pa_answers WHERE id = %d", $answer_id ) );
				if ( $answer ) {
					$score_total += $answer->weight;
					if ( ! isset( $label_totals[ $answer->personality_label ] ) ) {
						$label_totals[ $answer->personality_label ] = 0;
					}
					$label_totals[ $answer->personality_label ] += $answer->weight;
				}
			}

			$raw_payload[ $question_id ] = $answer_ids;
		}

		$dominant_label = '';
		if ( ! empty( $label_totals ) ) {
			// Sort by value descending, then by key ascending
			uksort(
				$label_totals,
				function ( $a, $b ) use ( $label_totals ) {
					if ( $label_totals[ $a ] > $label_totals[ $b ] ) {
						return -1;
					} elseif ( $label_totals[ $a ] < $label_totals[ $b ] ) {
						return 1;
					} else {
						return strcmp( $a, $b );
					}
				}
			);
			$dominant_label = key( $label_totals );
		}

		return array(
			'score_total'    => $score_total,
			'dominant_label' => $dominant_label,
			'raw_payload'    => $raw_payload,
		);
	}
}
