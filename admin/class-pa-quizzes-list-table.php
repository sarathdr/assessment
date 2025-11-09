<?php
/**
 * Quizzes List Table
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
 * The list table for quizzes.
 */
class Quizzes_List_Table extends \WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'quiz',
				'plural'   => 'quizzes',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get the column definitions.
	 */
	public function get_columns() {
		return array(
			'cb'            => '<input type="checkbox" />',
			'title'         => __( 'Title', 'personality-assessment' ),
			'status'        => __( 'Status', 'personality-assessment' ),
			'require_login' => __( 'Requires Login', 'personality-assessment' ),
			'shortcode'     => __( 'Shortcode', 'personality-assessment' ),
			'created_at'    => __( 'Created At', 'personality-assessment' ),
			'updated_at'    => __( 'Updated At', 'personality-assessment' ),
		);
	}

	/**
	 * Prepare the items for the table.
	 */
	public function prepare_items() {
		global $wpdb;

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$this->items = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}pa_quizzes", ARRAY_A );
	}

	/**
	 * Render the checkbox column.
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="quiz[]" value="%s" />', $item['id'] );
	}

	/**
	 * Render the title column.
	 */
	public function column_title( $item ) {
		$delete_nonce = wp_create_nonce( 'pa_delete_quiz' );
		$actions      = array(
			'edit'   => sprintf( '<a href="?page=personality-assessment&action=edit&id=%s">%s</a>', $item['id'], __( 'Edit', 'personality-assessment' ) ),
			'delete' => sprintf(
				'<a href="?page=%s&action=delete_quiz&id=%s&_wpnonce=%s" class="pa-delete-quiz">%s</a>',
				esc_attr( $_REQUEST['page'] ),
				absint( $item['id'] ),
				$delete_nonce,
				__( 'Delete', 'personality-assessment' )
			),
		);

		return sprintf( '%1$s %2$s', $item['title'], $this->row_actions( $actions ) );
	}

	/**
	 * Render the shortcode column.
	 */
	public function column_shortcode( $item ) {
		return sprintf( '[pa_quiz id="%s"]', $item['id'] );
	}

	/**
	 * Render the default column.
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}
}
