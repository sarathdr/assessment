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

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * The quizzes list table.
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
	 * Get the columns.
	 */
	public function get_columns() {
		return array(
			'cb'            => '<input type="checkbox" />',
			'title'         => __( 'Title', 'personality-assessment' ),
			'shortcode'     => __( 'Shortcode', 'personality-assessment' ),
			'questions'     => __( 'Questions', 'personality-assessment' ),
			'status'        => __( 'Status', 'personality-assessment' ),
			'require_login' => __( 'Requires Login', 'personality-assessment' ),
			'created_at'    => __( 'Created At', 'personality-assessment' ),
			'updated_at'    => __( 'Updated At', 'personality-assessment' ),
		);
	}

	/**
	 * Prepare the items.
	 */
	public function prepare_items() {
		global $wpdb;

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$this->items           = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}pa_quizzes" );
	}

	/**
	 * The checkbox column.
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="quiz[]" value="%s" />', $item->id );
	}

	/**
	 * The title column.
	 */
	public function column_title( $item ) {
		$actions = array(
			'edit'   => sprintf( '<a href="?page=%s&action=%s&id=%s">' . __( 'Edit', 'personality-assessment' ) . '</a>', $_REQUEST['page'], 'edit', $item->id ),
			'delete' => sprintf( '<a href="?page=%s&action=%s&id=%s&_wpnonce=%s" class="pa-delete-quiz">' . __( 'Delete', 'personality-assessment' ) . '</a>', $_REQUEST['page'], 'delete_quiz', $item->id, wp_create_nonce( 'pa_delete_quiz' ) ),
		);
		return sprintf( '%1$s %2$s', $item->title, $this->row_actions( $actions ) );
	}

	/**
	 * The shortcode column.
	 */
	public function column_shortcode( $item ) {
		return sprintf( '[pa_quiz id="%s"]', $item->id );
	}

	/**
	 * The questions column.
	 */
	public function column_questions( $item ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}pa_questions WHERE quiz_id = %d", $item->id ) );
	}

	/**
	 * The status column.
	 */
	public function column_status( $item ) {
		return ucfirst( $item->status );
	}

	/**
	 * The require login column.
	 */
	public function column_require_login( $item ) {
		return $item->require_login ? __( 'Yes', 'personality-assessment' ) : __( 'No', 'personality-assessment' );
	}

	/**
	 * The created at column.
	 */
	public function column_created_at( $item ) {
		return $item->created_at;
	}

	/**
	 * The updated at column.
	 */
	public function column_updated_at( $item ) {
		return $item->updated_at;
	}

	/**
	 * Get the sortable columns.
	 */
	protected function get_sortable_columns() {
		return array(
			'title'      => array( 'title', false ),
			'created_at' => array( 'created_at', false ),
			'updated_at' => array( 'updated_at', false ),
		);
	}
}
