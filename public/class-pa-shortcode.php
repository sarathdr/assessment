<?php
/**
 * Shortcode
 *
 * @package           PersonalityAssessment
 * @author            Jules
 * @copyright         2024 Jules
 * @license           GPL-3.0-or-later
 *
 * @wordpress-plugin
 */

namespace PA\Public;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The public-facing functionality of the plugin.
 */
class Shortcode {

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
		add_shortcode( 'pa_quiz', array( $this, 'render_quiz' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_pa_submit_quiz', array( $this, 'submit_quiz' ) );
		add_action( 'wp_ajax_nopriv_pa_submit_quiz', array( $this, 'submit_quiz' ) );
	}

	/**
	 * Enqueue the scripts and styles.
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'pa-public-style', PA_PLUGIN_URL . 'assets/css/public.css', array(), PA_PLUGIN_VERSION );
		wp_enqueue_script( 'pa-public-script', PA_PLUGIN_URL . 'assets/js/public.js', array( 'jquery' ), PA_PLUGIN_VERSION, true );
		wp_localize_script(
			'pa-public-script',
			'pa_quiz',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'pa_submit_quiz' ),
			)
		);
	}

	/**
	 * Render the quiz.
	 */
	public function render_quiz( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'pa_quiz'
		);

		$quiz_id = intval( $atts['id'] );

		if ( ! $quiz_id ) {
			return '';
		}

		global $wpdb;

		$quiz = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pa_quizzes WHERE id = %d AND status = 'published'", $quiz_id ) );

		if ( ! $quiz ) {
			return '';
		}

		if ( $quiz->require_login && ! is_user_logged_in() ) {
			return sprintf(
				'<p>%s <a href="%s">%s</a></p>',
				esc_html__( 'You must be logged in to take this quiz.', 'personality-assessment' ),
				esc_url( wp_login_url( get_permalink() ) ),
				esc_html__( 'Log in', 'personality-assessment' )
			);
		}

		$questions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pa_questions WHERE quiz_id = %d ORDER BY position ASC", $quiz_id ) );

		ob_start();
		?>
		<div class="pa-quiz-container" data-quiz-id="<?php echo esc_attr( $quiz_id ); ?>">
			<div class="pa-progress-bar">
				<div class="pa-progress-bar-inner" style="width: 0%;"></div>
			</div>
			<div class="pa-questions">
				<?php foreach ( $questions as $index => $question ) : ?>
					<div class="pa-question <?php echo ( 0 === $index ) ? 'active' : ''; ?>" data-question-id="<?php echo esc_attr( $question->id ); ?>">
						<h2><?php echo esc_html( $question->title ); ?></h2>
						<?php
						$answers = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pa_answers WHERE question_id = %d ORDER BY position ASC", $question->id ) );
						switch ( $question->type ) {
							case 'text':
								?>
								<textarea name="answers[<?php echo esc_attr( $question->id ); ?>]"></textarea>
								<?php
								break;
							case 'single':
								?>
								<div class="pa-answers">
									<?php foreach ( $answers as $answer ) : ?>
										<label class="pa-answer">
											<input type="radio" name="answers[<?php echo esc_attr( $question->id ); ?>]" value="<?php echo esc_attr( $answer->id ); ?>">
											<span><?php echo esc_html( $answer->label ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>
								<?php
								break;
							case 'multiple':
								?>
								<div class="pa-answers">
									<?php foreach ( $answers as $answer ) : ?>
										<label class="pa-answer">
											<input type="checkbox" name="answers[<?php echo esc_attr( $question->id ); ?>][]" value="<?php echo esc_attr( $answer->id ); ?>">
											<span><?php echo esc_html( $answer->label ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>
								<?php
								break;
						}
						?>
					</div>
				<?php endforeach; ?>
			</div>
			<div class="pa-navigation">
				<button class="pa-prev-btn" style="display: none;"><?php esc_html_e( 'Previous', 'personality-assessment' ); ?></button>
				<button class="pa-next-btn"><?php esc_html_e( 'Next', 'personality-assessment' ); ?></button>
				<button class="pa-submit-btn" style="display: none;"><?php esc_html_e( 'Submit', 'personality-assessment' ); ?></button>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Submit the quiz.
	 */
	public function submit_quiz() {
		check_ajax_referer( 'pa_submit_quiz', 'nonce' );

		$quiz_id = intval( $_POST['quiz_id'] );
		$answers = $_POST['answers'];

		global $wpdb;

		$score_data = \PA\Scoring::calculate_score( $quiz_id, $answers );

		$result_data = array(
			'quiz_id'        => $quiz_id,
			'user_id'        => get_current_user_id(),
			'attempt_uuid'   => wp_generate_uuid4(),
			'submitted_at'   => current_time( 'mysql' ),
			'score_total'    => $score_data['score_total'],
			'dominant_label' => $score_data['dominant_label'],
			'raw_payload'    => wp_json_encode( $score_data['raw_payload'] ),
		);

		$wpdb->insert( "{$wpdb->prefix}pa_results", $result_data );

		$quiz = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pa_quizzes WHERE id = %d", $quiz_id ) );

		if ( $quiz->webhook_url ) {
			$payload = wp_json_encode( $result_data );
			$signature = hash_hmac( 'sha256', $payload, $quiz->webhook_secret );

			wp_remote_post(
				$quiz->webhook_url,
				array(
					'headers' => array(
						'Content-Type'   => 'application/json',
						'X-PA-Signature' => 'sha256=' . $signature,
					),
					'body'    => $payload,
				)
			);
		}

		wp_send_json_success(
			array(
				'dominant_label' => $score_data['dominant_label'],
				'score_total'    => $score_data['score_total'],
			)
		);
	}
}
