<?php
/**
 * Results List Table
 *
 * @package           PersonalityAssessment
 * @author            Jules
 * @copyright         2024 Jules
 * @license           GPL-3.0-or-later
 *
 * @wordpress-plugin
 */

namespace PA\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * The list table for results.
 */
class Results_List_Table extends \WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'result',
				'plural'   => 'results',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get the column definitions.
	 */
	public function get_columns() {
		return array(
			'cb'             => '<input type="checkbox" />',
			'user'           => __( 'User', 'personality-assessment' ),
			'quiz'           => __( 'Quiz', 'personality-assessment' ),
			'submitted_at'   => __( 'Submitted At', 'personality-assessment' ),
			'dominant_label' => __( 'Dominant Label', 'personality-assessment' ),
			'score_total'    => __( 'Score Total', 'personality-assessment' ),
		);
	}

	/**
	 * Prepare the items for the table.
	 */
	public function prepare_items() {
		global $wpdb;

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$this->items = $wpdb->get_results(
			"SELECT r.*, q.title as quiz_title, u.display_name as user_name
			FROM {$wpdb->prefix}pa_results r
			LEFT JOIN {$wpdb->prefix}pa_quizzes q ON r.quiz_id = q.id
			LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID",
			ARRAY_A
		);
	}

	/**
	 * Render the checkbox column.
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="result[]" value="%s" />', $item['id'] );
	}

	/**
	 * Render the user column.
	 */
	public function column_user( $item ) {
		return $item['user_name'] ? $item['user_name'] : __( 'Guest', 'personality-assessment' );
	}

	/**
	 * Render the quiz column.
	 */
	public function column_quiz( $item ) {
		return $item['quiz_title'];
	}

	/**
	 * Render the default column.
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}
}
