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

		$query = "SELECT r.*, q.title as quiz_title, u.display_name as user_name
			FROM {$wpdb->prefix}pa_results r
			LEFT JOIN {$wpdb->prefix}pa_quizzes q ON r.quiz_id = q.id
			LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID";

		$where = array();

		$taxonomies = get_object_taxonomies( 'user' );
		foreach ( $taxonomies as $taxonomy ) {
			if ( ! empty( $_REQUEST[ $taxonomy ] ) ) {
				$term_id = intval( $_REQUEST[ $taxonomy ] );
				$user_ids = get_objects_in_term( $term_id, $taxonomy );
				if ( ! empty( $user_ids ) ) {
					$where[] = "r.user_id IN (" . implode( ',', $user_ids ) . ")";
				}
			}
		}

		if ( ! empty( $where ) ) {
			$query .= " WHERE " . implode( ' AND ', $where );
		}

		$this->items = $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Add extra table navigation.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$taxonomies = get_object_taxonomies( 'user' );

		if ( empty( $taxonomies ) ) {
			return;
		}

		echo '<div class="alignleft actions">';

		foreach ( $taxonomies as $taxonomy ) {
			$tax = get_taxonomy( $taxonomy );
			$terms = get_terms( $taxonomy, array( 'hide_empty' => false ) );
			if ( $terms ) {
				printf( '<label for="filter-by-%1$s" class="screen-reader-text">%2$s</label>', esc_attr( $taxonomy ), esc_html( $tax->labels->singular_name ) );
				$selected = ! empty( $_REQUEST[ $taxonomy ] ) ? $_REQUEST[ $taxonomy ] : '';
				wp_dropdown_categories(
					array(
						'show_option_all' => $tax->labels->all_items,
						'taxonomy'        => $taxonomy,
						'name'            => $taxonomy,
						'orderby'         => 'name',
						'selected'        => $selected,
						'hierarchical'    => true,
						'show_count'      => false,
						'hide_empty'      => true,
					)
				);
			}
		}

		submit_button( __( 'Filter' ), 'button', 'filter_action', false );

		echo '</div>';
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
		$user_display = $item['user_name'] ? esc_html( $item['user_name'] ) : __( 'Guest', 'personality-assessment' );

		$actions = array(
			'view' => sprintf(
				'<a href="?page=%s&action=view&id=%s">%s</a>',
				esc_attr( $_REQUEST['page'] ),
				absint( $item['id'] ),
				__( 'View', 'personality-assessment' )
			),
		);

		return $user_display . $this->row_actions( $actions );
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
