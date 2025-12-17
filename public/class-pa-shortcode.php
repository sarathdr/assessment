<?php
/**
 * Shortcode
 *
 * @package           PersonalityAssessment
 * @author            Sarath
 * @copyright         2025 Drizzle limited
 * @license           Contact: sarath@drizzle.media
 *
 * @wordpress-plugin
 */

namespace PA\Public;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * The public-facing functionality of the plugin.
 */
class Shortcode
{

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct()
	{
		add_shortcode('pa_quiz', array($this, 'render_quiz'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('wp_ajax_pa_submit_quiz', array($this, 'submit_quiz'));
		add_action('wp_ajax_nopriv_pa_submit_quiz', array($this, 'submit_quiz'));
	}

	/**
	 * Enqueue the scripts and styles.
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_style('pa-public-style', PA_PLUGIN_URL . 'assets/css/public.css', array(), PA_PLUGIN_VERSION);
		wp_enqueue_script('pa-public-script', PA_PLUGIN_URL . 'assets/js/public.js', array('jquery'), PA_PLUGIN_VERSION, true);
		wp_localize_script(
			'pa-public-script',
			'pa_quiz',
			array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('pa_submit_quiz'),
			)
		);
	}

	/**
	 * Render the quiz.
	 */
	public function render_quiz($atts)
	{
		$atts = shortcode_atts(
			array(
				'id' => 0,
				'show_title' => 'true',
				'show_progress' => 'true',
			),
			$atts,
			'pa_quiz'
		);

		$quiz_id = intval($atts['id']);

		if (!$quiz_id) {
			return '';
		}

		global $wpdb;

		$quiz = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pa_quizzes WHERE id = %d AND status = 'published'", $quiz_id));

		if (!$quiz) {
			return '';
		}

		if ($quiz->require_login && !is_user_logged_in()) {
			return sprintf(
				'<p>%s <a href="%s">%s</a></p>',
				esc_html__('You must be logged in to take this quiz.', 'personality-assessment'),
				esc_url(wp_login_url(get_permalink())),
				esc_html__('Log in', 'personality-assessment')
			);
		}

		$questions = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pa_questions WHERE quiz_id = %d ORDER BY position ASC", $quiz_id));

		ob_start();
		?>
		<div class="pa-quiz-container" data-quiz-id="<?php echo esc_attr($quiz_id); ?>">


			<!-- Step Progress -->
			<?php if ('true' === $atts['show_progress']): ?>
				<div class="pa-steps-progress">
					<?php foreach ($questions as $index => $q): ?>
						<div class="pa-step <?php echo (0 === $index) ? 'current' : ''; ?>"
							data-step="<?php echo esc_attr($index + 1); ?>">
							<?php echo esc_html($index + 1); ?>
						</div>
					<?php endforeach; ?>
				</div>

				<!-- Legend -->
				<div class="pa-legend">
					<div class="pa-legend-item"><span class="pa-legend-box current"></span>
						<?php esc_html_e('Current', 'personality-assessment'); ?></div>
					<div class="pa-legend-item"><span class="pa-legend-box review"></span>
						<?php esc_html_e('Review', 'personality-assessment'); ?></div>
					<div class="pa-legend-item"><span class="pa-legend-box answered"></span>
						<?php esc_html_e('Answered', 'personality-assessment'); ?></div>
				</div>
			<?php endif; ?>

			<!-- Review Button -->
			<div class="pa-review-action">
				<button type="button"
					class="pa-btn-review"><?php esc_html_e('Review Question', 'personality-assessment'); ?></button>
			</div>

			<div class="pa-questions">
				<?php foreach ($questions as $index => $question): ?>
					<div class="pa-question <?php echo (0 === $index) ? 'active' : ''; ?>"
						data-question-id="<?php echo esc_attr($question->id); ?>">
						<div class="pa-question-content">
							<h2><?php echo esc_html($question->title); ?></h2>
							<?php
							$answers = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pa_answers WHERE question_id = %d ORDER BY position ASC", $question->id));
							switch ($question->type) {
								case 'text':
									?>
									<textarea name="answers[<?php echo esc_attr($question->id); ?>]"></textarea>
									<?php
									break;
								case 'single':
									?>
									<div class="pa-answers">
										<?php foreach ($answers as $answer): ?>
											<label class="pa-answer-card">
												<input type="radio" name="answers[<?php echo esc_attr($question->id); ?>]"
													value="<?php echo esc_attr($answer->id); ?>">
												<span class="pa-answer-content">
													<span class="pa-control-ring"></span>
													<span class="pa-answer-text"><?php echo esc_html($answer->label); ?></span>
												</span>
											</label>
										<?php endforeach; ?>
									</div>
									<?php
									break;
								case 'multiple':
									?>
									<div class="pa-answers">
										<?php foreach ($answers as $answer): ?>
											<label class="pa-answer-card">
												<input type="checkbox" name="answers[<?php echo esc_attr($question->id); ?>][]"
													value="<?php echo esc_attr($answer->id); ?>">
												<span class="pa-answer-content">
													<span class="pa-control-box"></span>
													<span class="pa-answer-text"><?php echo esc_html($answer->label); ?></span>
												</span>
											</label>
										<?php endforeach; ?>
									</div>
									<?php
									break;
							}
							?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<div class="pa-navigation">
				<button class="pa-prev-btn"
					style="display: none;"><?php esc_html_e('Previous', 'personality-assessment'); ?></button>
				<button class="pa-next-btn"><?php esc_html_e('Next', 'personality-assessment'); ?></button>
				<button class="pa-submit-btn"
					style="display: none;"><?php esc_html_e('Finish Assessment', 'personality-assessment'); ?></button>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Submit the quiz.
	 */
	public function submit_quiz()
	{
		check_ajax_referer('pa_submit_quiz', 'nonce');

		$quiz_id = intval($_POST['quiz_id']);
		$answers = $_POST['answers'];

		global $wpdb;

		$score_data = \PA\Scoring::calculate_score($quiz_id, $answers);

		$result_data = array(
			'quiz_id' => $quiz_id,
			'user_id' => get_current_user_id(),
			'attempt_uuid' => wp_generate_uuid4(),
			'submitted_at' => current_time('mysql'),
			'score_total' => $score_data['score_total'],
			'dominant_label' => $score_data['dominant_label'],
			'label_scores' => wp_json_encode($score_data['label_scores']),
			'raw_payload' => wp_json_encode($score_data['raw_payload']),
		);

		$wpdb->insert("{$wpdb->prefix}pa_results", $result_data);

		$quiz = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pa_quizzes WHERE id = %d", $quiz_id));

		if ($quiz->webhook_url) {
			$payload = wp_json_encode($result_data);
			$signature = hash_hmac('sha256', $payload, $quiz->webhook_secret);

			wp_remote_post(
				$quiz->webhook_url,
				array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'X-PA-Signature' => 'sha256=' . $signature,
					),
					'body' => $payload,
				)
			);
		}

		$summary_html = $this->render_results_summary($score_data['label_scores'], $quiz_id);
		$success_message = str_replace('[pa_results_summary]', $summary_html, $quiz->success_message);

		wp_send_json_success(
			array(
				'success_message' => do_shortcode($success_message),
			)
		);
	}

	/**
	 * Render the results summary HTML.
	 *
	 * @param array $label_scores The label scores.
	 * @param int $quiz_id The quiz ID.
	 * @return string The HTML.
	 */
	public function render_results_summary($label_scores, $quiz_id)
	{
		if (empty($label_scores)) {
			return '';
		}

		// Sort scores descending
		arsort($label_scores);

		// Take top 5
		$top_labels = array_slice($label_scores, 0, 5, true);

		global $wpdb;

		ob_start();
		?>
		<div class="pa-results-summary-cards">
			<?php foreach ($top_labels as $label => $score): ?>
				<?php
				// Fetch label settings
				$settings = $wpdb->get_row($wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}pa_quiz_labels WHERE quiz_id = %d AND label_name = %s",
					$quiz_id,
					$label
				));

				$heading = $settings && $settings->heading ? $settings->heading : $label;
				$sub_heading = $settings ? $settings->sub_heading : '';
				$icon_url = $settings ? $settings->icon_url : '';
				$landing_page_url = $settings ? $settings->landing_page_url : '#';
				?>
				<a href="<?php echo esc_url($landing_page_url); ?>" class="pa-result-card" target="_blank">
					<div class="pa-result-card-icon">
						<?php if ($icon_url): ?>
							<img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($label); ?>">
						<?php else: ?>
							<span class="pa-default-icon">★</span>
						<?php endif; ?>
					</div>
					<div class="pa-result-card-content">
						<h4 class="pa-result-card-heading"><?php echo esc_html($heading); ?></h4>
						<?php if ($sub_heading): ?>
							<p class="pa-result-card-subheading"><?php echo esc_html($sub_heading); ?></p>
						<?php endif; ?>
					</div>

				</a>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
