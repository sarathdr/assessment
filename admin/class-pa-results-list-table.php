<?php
/**
 * Results List Table
 *
 * @package           PersonalityAssessment
 * @author            Sarath
 * @copyright         2025 Drizzle limited
 * @license           Contact: sarath@drizzle.media
 *
 * @wordpress-plugin
 */

namespace PA\Admin;

if (!class_exists('WP_List_Table')) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * The results list table.
 */
class Results_List_Table extends \WP_List_Table
{

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct(
			array(
				'singular' => 'result',
				'plural' => 'results',
				'ajax' => false,
			)
		);
	}

	/**
	 * Get the columns.
	 */
	public function get_columns()
	{
		return array(
			'cb' => '<input type="checkbox" />',
			'quiz_title' => __('Quiz', 'personality-assessment'),
			'user' => __('User', 'personality-assessment'),
			'dominant_label' => __('Dominant Label', 'personality-assessment'),
			'submitted_at' => __('Submitted At', 'personality-assessment'),
		);
	}

	/**
	 * Prepare the items.
	 */
	public function prepare_items()
	{
		global $wpdb;

		$this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());

		$query = "SELECT r.*, q.title as quiz_title, u.display_name as user_name
			FROM {$wpdb->prefix}pa_results r
			LEFT JOIN {$wpdb->prefix}pa_quizzes q ON r.quiz_id = q.id
			LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID";

		$taxonomies = get_object_taxonomies('user', 'objects');
		$where = array();

		foreach ($taxonomies as $taxonomy) {
			if (isset($_REQUEST[$taxonomy->name]) && !empty($_REQUEST[$taxonomy->name])) {
				$term_id = intval($_REQUEST[$taxonomy->name]);
				$user_ids = get_objects_in_term($term_id, $taxonomy->name);
				if (!empty($user_ids)) {
					$where[] = 'r.user_id IN (' . implode(',', $user_ids) . ')';
				}
			}
		}

		if (!empty($where)) {
			$query .= ' WHERE ' . implode(' AND ', $where);
		}

		$this->items = $wpdb->get_results($query);
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 */
	protected function extra_tablenav($which)
	{
		if ('top' !== $which) {
			return;
		}

		$taxonomies = get_object_taxonomies('user', 'objects');
		?>
		<div class="alignleft actions">
			<?php
			foreach ($taxonomies as $taxonomy) {
				wp_dropdown_categories(
					array(
						'show_option_all' => sprintf('All %s', $taxonomy->label),
						'taxonomy' => $taxonomy->name,
						'name' => $taxonomy->name,
						'orderby' => 'name',
						'selected' => isset($_REQUEST[$taxonomy->name]) ? $_REQUEST[$taxonomy->name] : '',
						'hierarchical' => true,
						'show_count' => false,
						'hide_empty' => true,
					)
				);
			}
			submit_button(__('Filter'), 'button', false, false, array('id' => 'post-query-submit'));
			?>
		</div>
		<?php
	}


	/**
	 * The checkbox column.
	 */
	public function column_cb($item)
	{
		return sprintf('<input type="checkbox" name="result[]" value="%s" />', $item->id);
	}

	/**
	 * The quiz title column.
	 */
	public function column_quiz_title($item)
	{
		$actions = array(
			'view' => sprintf('<a href="?page=pa-results&action=view&id=%s">' . __('View', 'personality-assessment') . '</a>', $item->id),
		);
		return sprintf('%1$s %2$s', $item->quiz_title, $this->row_actions($actions));
	}

	/**
	 * The user column.
	 */
	public function column_user($item)
	{
		return $item->user_name ? $item->user_name : __('Guest', 'personality-assessment');
	}

	/**
	 * The dominant label column.
	 */
	public function column_dominant_label($item)
	{
		return $item->dominant_label;
	}

	/**
	 * The submitted at column.
	 */
	public function column_submitted_at($item)
	{
		return $item->submitted_at;
	}

	/**
	 * Get the sortable columns.
	 */
	protected function get_sortable_columns()
	{
		return array(
			'quiz_title' => array('quiz_title', false),
			'submitted_at' => array('submitted_at', false),
		);
	}
}
