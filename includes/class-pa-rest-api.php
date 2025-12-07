<?php
/**
 * REST API
 *
 * @package           PersonalityAssessment
 * @author            Sarath
 * @copyright         2025 Drizzle limited
 * @license           Contact: sarath@drizzle.media
 *
 * @wordpress-plugin
 */

namespace PA;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * The REST API functionality of the plugin.
 */
class REST_API
{

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct()
	{
		add_action('rest_api_init', array($this, 'register_routes'));
	}

	/**
	 * Register the REST API routes.
	 */
	public function register_routes()
	{
		register_rest_route(
			'pa/v1',
			'/quizzes',
			array(
				'methods' => 'GET',
				'callback' => array($this, 'get_quizzes'),
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				},
			)
		);

		register_rest_route(
			'pa/v1',
			'/quizzes/(?P<id>\d+)',
			array(
				'methods' => 'GET',
				'callback' => array($this, 'get_quiz'),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'pa/v1',
			'/quizzes/(?P<id>\d+)/attempts',
			array(
				'methods' => 'POST',
				'callback' => array($this, 'start_attempt'),
				'permission_callback' => array($this, 'can_take_quiz'),
			)
		);

		register_rest_route(
			'pa/v1',
			'/quizzes/(?P<id>\d+)/submit',
			array(
				'methods' => 'POST',
				'callback' => array($this, 'submit_answers'),
				'permission_callback' => array($this, 'can_take_quiz'),
			)
		);

		register_rest_route(
			'pa/v1',
			'/results/(?P<uuid>[a-f0-9\-]+)',
			array(
				'methods' => 'GET',
				'callback' => array($this, 'get_result'),
				'permission_callback' => array($this, 'can_view_result'),
			)
		);
	}

	/**
	 * Get a quiz.
	 */
	public function get_quiz($request)
	{
		$quiz_id = intval($request['id']);

		global $wpdb;

		$quiz = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pa_quizzes WHERE id = %d AND status = 'published'", $quiz_id));

		if (!$quiz) {
			return new \WP_Error('not_found', __('Quiz not found.', 'personality-assessment'), array('status' => 404));
		}

		$questions = $wpdb->get_results($wpdb->prepare("SELECT id, title, type, is_required FROM {$wpdb->prefix}pa_questions WHERE quiz_id = %d ORDER BY position ASC", $quiz_id));

		foreach ($questions as &$question) {
			$question->answers = $wpdb->get_results($wpdb->prepare("SELECT id, label FROM {$wpdb->prefix}pa_answers WHERE question_id = %d ORDER BY position ASC", $question->id));
		}

		$quiz->questions = $questions;

		return new \WP_REST_Response($quiz);
	}

	/**
	 * Get all quizzes.
	 */
	public function get_quizzes($request)
	{
		global $wpdb;

		$quizzes = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}pa_quizzes");

		return new \WP_REST_Response($quizzes);
	}

	/**
	 * Start a quiz attempt.
	 */
	public function start_attempt($request)
	{
		$quiz_id = intval($request['id']);

		global $wpdb;

		$quiz = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pa_quizzes WHERE id = %d AND status = 'published'", $quiz_id));

		if (!$quiz) {
			return new \WP_Error('not_found', __('Quiz not found.', 'personality-assessment'), array('status' => 404));
		}

		$attempt_uuid = wp_generate_uuid4();

		return new \WP_REST_Response(array('attempt_uuid' => $attempt_uuid));
	}

	/**
	 * Submit quiz answers.
	 */
	public function submit_answers($request)
	{
		$quiz_id = intval($request['id']);
		$attempt_uuid = $request['attempt_uuid'];
		$answers = $request['answers'];

		global $wpdb;

		$score_data = Scoring::calculate_score($quiz_id, $answers);

		$result_data = array(
			'quiz_id' => $quiz_id,
			'user_id' => get_current_user_id(),
			'attempt_uuid' => $attempt_uuid,
			'submitted_at' => current_time('mysql'),
			'score_total' => $score_data['score_total'],
			'dominant_label' => $score_data['dominant_label'],
			'raw_payload' => wp_json_encode($score_data['raw_payload']),
		);

		$wpdb->insert("{$wpdb->prefix}pa_results", $result_data);

		return new \WP_REST_Response(
			array(
				'dominant_label' => $score_data['dominant_label'],
				'score_total' => $score_data['score_total'],
			)
		);
	}

	/**
	 * Get a result.
	 */
	public function get_result($request)
	{
		$uuid = $request['uuid'];

		global $wpdb;

		$result = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pa_results WHERE attempt_uuid = %s", $uuid));

		if (!$result) {
			return new \WP_Error('not_found', __('Result not found.', 'personality-assessment'), array('status' => 404));
		}

		return new \WP_REST_Response($result);
	}

	/**
	 * Check if the user can take the quiz.
	 */
	public function can_take_quiz($request)
	{
		$quiz_id = intval($request['id']);

		global $wpdb;

		$quiz = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pa_quizzes WHERE id = %d", $quiz_id));

		if (!$quiz) {
			return new \WP_Error('not_found', __('Quiz not found.', 'personality-assessment'), array('status' => 404));
		}

		if ($quiz->require_login && !is_user_logged_in()) {
			return new \WP_Error('permission_denied', __('You must be logged in to take this quiz.', 'personality-assessment'), array('status' => 401));
		}

		return true;
	}

	/**
	 * Check if the user can view the result.
	 */
	public function can_view_result($request)
	{
		$uuid = $request['uuid'];

		global $wpdb;

		$result = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pa_results WHERE attempt_uuid = %s", $uuid));

		if (!$result) {
			return new \WP_Error('not_found', __('Result not found.', 'personality-assessment'), array('status' => 404));
		}

		if (current_user_can('manage_options')) {
			return true;
		}

		if (is_user_logged_in() && get_current_user_id() === (int) $result->user_id) {
			return true;
		}

		return new \WP_Error('permission_denied', __('You do not have permission to view this result.', 'personality-assessment'), array('status' => 403));
	}
}
