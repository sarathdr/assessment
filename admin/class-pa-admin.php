<?php
/**
 * Admin
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

/**
 * The admin-specific functionality of the plugin.
 */
class Admin {

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'save_quiz' ) );
		add_action( 'admin_init', array( $this, 'save_question' ) );
		add_action( 'admin_init', array( $this, 'save_answer' ) );
		add_action( 'admin_init', array( $this, 'delete_question' ) );
		add_action( 'admin_init', array( $this, 'delete_answer' ) );
		add_action( 'admin_init', array( $this, 'create_quiz_and_redirect' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_ajax_pa_add_answer', array( $this, 'add_answer_ajax_handler' ) );
		add_action( 'wp_ajax_pa_delete_answer', array( $this, 'delete_answer_ajax_handler' ) );
		add_action( 'wp_ajax_pa_add_question', array( $this, 'add_question_ajax_handler' ) );
		add_action( 'wp_ajax_pa_save_question_details', array( $this, 'save_question_details_ajax_handler' ) );
		add_action( 'wp_ajax_pa_save_answer_details', array( $this, 'save_answer_details_ajax_handler' ) );
		add_action( 'wp_ajax_pa_save_question_title', array( $this, 'save_question_title_ajax_handler' ) );
		add_action( 'admin_init', array( $this, 'export_results_csv' ) );
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
	}

	/**
	 * Show admin notices.
	 */
	public function show_admin_notices() {
		if ( ! isset( $_GET['pa-export-error'] ) ) {
			return;
		}

		$error_code = sanitize_key( $_GET['pa-export-error'] );
		$message    = '';

		if ( '1' === $error_code ) {
			$message = __( 'Please select a quiz to export results.', 'personality-assessment' );
		} elseif ( '2' === $error_code ) {
			$message = __( 'The selected quiz has no results to export.', 'personality-assessment' );
		}

		if ( $message ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo esc_html( $message ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Export results to CSV
	 */
	public function export_results_csv() {
		if ( ! isset( $_POST['action'] ) || 'export_results_csv' !== $_POST['action'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$quiz_id = isset( $_POST['quiz_id'] ) ? intval( $_POST['quiz_id'] ) : 0;

		if ( ! $quiz_id ) {
			wp_safe_redirect( add_query_arg( array( 'pa-export-error' => '1' ), admin_url( 'admin.php?page=pa-results' ) ) );
			exit;
		}

		global $wpdb;

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pa_results WHERE quiz_id = %d", $quiz_id ) );

		if ( ! $results ) {
			wp_safe_redirect( add_query_arg( array( 'pa-export-error' => '2' ), admin_url( 'admin.php?page=pa-results' ) ) );
			exit;
		}

		$filename = 'quiz-results-' . $quiz_id . '-' . date( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$output = fopen( 'php://output', 'w' );

		// Get all possible labels from the results
		$all_labels = array();
		foreach ( $results as $result ) {
			$label_scores = json_decode( $result->label_scores, true );
			if ( is_array( $label_scores ) ) {
				foreach ( $label_scores as $label => $score ) {
					if ( ! in_array( $label, $all_labels ) ) {
						$all_labels[] = $label;
					}
				}
			}
		}
		sort( $all_labels );

		// Header row
		$header = array( 'user_id', 'submitted_at', 'score_total', 'dominant_label' );
		$header = array_merge( $header, $all_labels );
		fputcsv( $output, $header );

		// Data rows
		foreach ( $results as $result ) {
			$row          = array(
				$result->user_id,
				$result->submitted_at,
				$result->score_total,
				$result->dominant_label,
			);
			$label_scores = json_decode( $result->label_scores, true );
			if ( ! is_array( $label_scores ) ) {
				$label_scores = array();
			}
			foreach ( $all_labels as $label ) {
				$row[] = isset( $label_scores[ $label ] ) ? $label_scores[ $label ] : 0;
			}
			fputcsv( $output, $row );
		}

		fclose( $output );
		exit;
	}

	/**
	 * AJAX handler for adding an answer.
	 */
	public function add_answer_ajax_handler() {
		if ( ! isset( $_POST['pa_add_answer_nonce'] ) || ! wp_verify_nonce( $_POST['pa_add_answer_nonce'], 'pa_add_answer' ) ) {
			wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to do this.' ) );
		}

		global $wpdb;

		$question_id = intval( $_POST['question_id'] );
		$data        = array(
			'label'         => '',
			'label_weights' => '[]',
			'question_id'   => $question_id,
		);

		$position         = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}pa_answers WHERE question_id = %d", $question_id ) );
		$data['position'] = $position + 1;

		$wpdb->insert( "{$wpdb->prefix}pa_answers", $data );
		$answer_id = $wpdb->insert_id;
		$answer    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pa_answers WHERE id = %d", $answer_id ) );

		ob_start();
		?>
		<div class="answer-item" data-answer-id="<?php echo esc_attr( $answer->id ); ?>">
			<span class="dashicons dashicons-menu handle"></span>
			<input type="text" class="answer-label-input" value="" placeholder="<?php esc_attr_e( 'Answer Label', 'personality-assessment' ); ?>" />
			<div class="label-weights-wrapper">
			</div>
			<button class="button add-label-button"><?php esc_html_e( 'Add Label', 'personality-assessment' ); ?></button>
			<button class="button delete-answer-button" data-answer-id="<?php echo esc_attr( $answer->id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'pa_delete_answer_' . $answer->id ) ); ?>"><span class="dashicons dashicons-trash"></span></button>
		</div>
		<?php
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX handler for saving question title.
	 */
	public function save_question_title_ajax_handler() {
		if ( ! isset( $_POST['pa_save_question_title_nonce'] ) || ! wp_verify_nonce( $_POST['pa_save_question_title_nonce'], 'pa_save_question_title' ) ) {
			wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to do this.' ) );
		}

		global $wpdb;

		$question_id = intval( $_POST['question_id'] );
		$title       = sanitize_text_field( $_POST['title'] );

		$wpdb->update( "{$wpdb->prefix}pa_questions", array( 'title' => $title ), array( 'id' => $question_id ) );

		wp_send_json_success();
	}

	/**
	 * AJAX handler for saving answer details.
	 */
	public function save_answer_details_ajax_handler() {
		if ( ! isset( $_POST['pa_save_answer_details_nonce'] ) || ! wp_verify_nonce( $_POST['pa_save_answer_details_nonce'], 'pa_save_answer_details' ) ) {
			wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to do this.' ) );
		}

		global $wpdb;

		$answer_id = intval( $_POST['answer_id'] );

		$label_weights = isset( $_POST['label_weights'] ) ? json_decode( stripslashes( $_POST['label_weights'] ), true ) : array();
		$sanitized_label_weights = array();
		if ( is_array( $label_weights ) ) {
			foreach ( $label_weights as $label => $weight ) {
				$sanitized_label_weights[ sanitize_text_field( $label ) ] = floatval( $weight );
			}
		}

		$data = array(
			'label'         => sanitize_text_field( $_POST['label'] ),
			'label_weights' => wp_json_encode( $sanitized_label_weights ),
		);

		$wpdb->update( "{$wpdb->prefix}pa_answers", $data, array( 'id' => $answer_id ) );

		wp_send_json_success();
	}

	/**
	 * AJAX handler for deleting an answer.
	 */
	public function delete_answer_ajax_handler() {
		$answer_id = isset( $_POST['answer_id'] ) ? intval( $_POST['answer_id'] ) : 0;

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'pa_delete_answer_' . $answer_id ) ) {
			wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to do this.' ) );
		}

		global $wpdb;

		$answer_id = intval( $_POST['answer_id'] );
		$wpdb->delete( "{$wpdb->prefix}pa_answers", array( 'id' => $answer_id ) );

		wp_send_json_success();
	}

	/**
	 * AJAX handler for saving question details.
	 */
	public function save_question_details_ajax_handler() {
		if ( ! isset( $_POST['pa_save_question_details_nonce'] ) || ! wp_verify_nonce( $_POST['pa_save_question_details_nonce'], 'pa_save_question_details' ) ) {
			wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to do this.' ) );
		}

		global $wpdb;

		$question_id = intval( $_POST['question_id'] );

		$data = array(
			'type'        => sanitize_text_field( $_POST['question_type'] ),
			'is_required' => isset( $_POST['is_required'] ) ? 1 : 0,
		);

		$wpdb->update( "{$wpdb->prefix}pa_questions", $data, array( 'id' => $question_id ) );

		wp_send_json_success(
			array(
				'question_id' => $question_id,
				'type'        => $data['type'],
				'is_required' => $data['is_required'],
			)
		);
	}

	/**
	 * AJAX handler for adding a question.
	 */
	public function add_question_ajax_handler() {
		if ( ! isset( $_POST['pa_save_question_nonce'] ) || ! wp_verify_nonce( $_POST['pa_save_question_nonce'], 'pa_save_question' ) ) {
			wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to do this.' ) );
		}

		global $wpdb;

		$quiz_id = intval( $_POST['quiz_id'] );
		$data    = array(
			'title'       => sanitize_text_field( $_POST['question_title'] ),
			'type'        => 'single',
			'is_required' => 1,
			'quiz_id'     => $quiz_id,
		);

		$position         = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}pa_questions WHERE quiz_id = %d", $quiz_id ) );
		$data['position'] = $position + 1;

		$wpdb->insert( "{$wpdb->prefix}pa_questions", $data );
		$question_id = $wpdb->insert_id;
		$question    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pa_questions WHERE id = %d", $question_id ) );

		ob_start();
		$this->render_question_item( $question );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Render a single question item.
	 */
	public function render_question_item( $question ) {
		global $wpdb;
		?>
		<div class="question-item" data-question-id="<?php echo esc_attr( $question->id ); ?>" data-question-type="<?php echo esc_attr( $question->type ); ?>" data-is-required="<?php echo esc_attr( $question->is_required ); ?>">
			<?php wp_nonce_field( 'pa_save_question_title', 'pa_save_question_title_nonce_' . $question->id ); ?>
			<div class="question-title-header">
				<h3 class="question-title-text"><?php echo esc_html( $question->title ); ?></h3>
				<div class="question-actions">
					<button class="button edit-question-title"><span class="dashicons dashicons-edit"></span></button>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=personality-assessment&action=delete_question&question_id=' . $question->id ), 'pa_delete_question' ) ); ?>" class="button delete-question"><span class="dashicons dashicons-trash"></span></a>
				</div>
			</div>

			<div class="answers-section">
				<div class="answers-list">
					<?php
					$answers = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pa_answers WHERE question_id = %d ORDER BY position ASC", $question->id ) );
					if ( $answers ) {
						foreach ( $answers as $answer ) {
							?>
							<div class="answer-item" data-answer-id="<?php echo esc_attr( $answer->id ); ?>">
								<span class="dashicons dashicons-menu handle"></span>
								<input type="text" class="answer-label-input" value="<?php echo esc_attr( $answer->label ); ?>" />
								<div class="label-weights-wrapper">
									<?php
									$label_weights = json_decode( $answer->label_weights, true );
									if ( is_array( $label_weights ) ) {
										foreach ( $label_weights as $label => $weight ) {
											?>
											<div class="label-weight-item">
												<input type="text" class="answer-personality-label-input" value="<?php echo esc_attr( $label ); ?>" placeholder="<?php esc_attr_e( 'Label', 'personality-assessment' ); ?>" />
												<input type="number" class="answer-weight-input" value="<?php echo esc_attr( $weight ); ?>" placeholder="<?php esc_attr_e( 'Weight', 'personality-assessment' ); ?>" />
												<button class="button delete-label-button"><span class="dashicons dashicons-trash"></span></button>
											</div>
											<?php
										}
									}
									?>
								</div>
								<button class="button add-label-button"><?php esc_html_e( 'Add Label', 'personality-assessment' ); ?></button>
								<button class="button delete-answer-button" data-answer-id="<?php echo esc_attr( $answer->id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'pa_delete_answer_' . $answer->id ) ); ?>"><span class="dashicons dashicons-trash"></span></button>
							</div>
							<?php
						}
					}
					?>
				</div>
				<button class="button add-answer-button" data-question-id="<?php echo esc_attr( $question->id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'pa_add_answer' ) ); ?>"><?php esc_html_e( 'Add Answer', 'personality-assessment' ); ?></button>
				<?php wp_nonce_field( 'pa_save_answer_details', 'pa_save_answer_details_nonce' ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Create a new quiz and redirect to the edit screen.
	 */
	public function create_quiz_and_redirect() {
		if ( ! isset( $_GET['page'] ) || 'personality-assessment' !== $_GET['page'] ) {
			return;
		}

		if ( ! isset( $_GET['action'] ) || 'new' !== $_GET['action'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;

		$wpdb->insert(
			"{$wpdb->prefix}pa_quizzes",
			array(
				'title'  => __( 'New Quiz', 'personality-assessment' ),
				'slug'   => 'new-quiz-' . time(),
				'status' => 'draft',
			)
		);

		$quiz_id = $wpdb->insert_id;

		wp_safe_redirect( admin_url( 'admin.php?page=personality-assessment&action=edit&id=' . $quiz_id ) );
		exit;
	}

	/**
	 * Enqueue the styles.
	 */
	public function enqueue_styles( $hook ) {
		if ( strpos( $hook, 'personality-assessment' ) === false ) {
			return;
		}

		wp_enqueue_style( 'pa-admin-style', PA_PLUGIN_URL . 'assets/css/admin.css', array(), PA_PLUGIN_VERSION );
		wp_enqueue_script( 'pa-admin-script', PA_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), PA_PLUGIN_VERSION, true );
	}

	/**
	 * Add the admin menu and submenus.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Personality Assessment', 'personality-assessment' ),
			__( 'Personality Assessment', 'personality-assessment' ),
			'manage_options',
			'personality-assessment',
			array( $this, 'render_quizzes_page' ),
			'dashicons-forms',
			20
		);

		add_submenu_page(
			'personality-assessment',
			__( 'Quizzes', 'personality-assessment' ),
			__( 'Quizzes', 'personality-assessment' ),
			'manage_options',
			'personality-assessment',
			array( $this, 'render_quizzes_page' )
		);

		add_submenu_page(
			'personality-assessment',
			__( 'Labels', 'personality-assessment' ),
			__( 'Labels', 'personality-assessment' ),
			'manage_options',
			'edit.php?taxonomy=pa_personality_label'
		);

		add_submenu_page(
			'personality-assessment',
			__( 'Results', 'personality-assessment' ),
			__( 'Results', 'personality-assessment' ),
			'manage_options',
			'pa-results',
			array( $this, 'render_results_page' )
		);

		add_submenu_page(
			'personality-assessment',
			__( 'Settings', 'personality-assessment' ),
			__( 'Settings', 'personality-assessment' ),
			'manage_options',
			'pa-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render the quizzes page.
	 */
	public function render_quizzes_page() {
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';

		switch ( $action ) {
			case 'edit':
				$this->render_quiz_form();
				break;
			default:
				$this->render_quizzes_list();
				break;
		}
	}

	/**
	 * Render the quizzes list.
	 */
	public function render_quizzes_list() {

		require_once PA_PLUGIN_DIR . 'admin/class-pa-quizzes-list-table.php';
		$list_table = new Quizzes_List_Table();
		$list_table->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Quizzes', 'personality-assessment' ); ?></h1>
			<a href="?page=personality-assessment&action=new" class="page-title-action"><?php esc_html_e( 'Add New', 'personality-assessment' ); ?></a>
			<hr class="wp-header-end">
			<form method="post">
				<?php
				$list_table->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the quiz form.
	 */
	public function render_quiz_form() {
		global $wpdb;

		$quiz_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
		$quiz    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pa_quizzes WHERE id = %d", $quiz_id ) );

		if ( isset( $_GET['message'] ) && '1' === $_GET['message'] ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Quiz saved successfully.', 'personality-assessment' ); ?></p>
			</div>
			<?php
		}
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php esc_html_e( 'Edit Quiz', 'personality-assessment' ); ?>
			</h1>
			<a href="?page=personality-assessment" class="page-title-action"><?php esc_html_e( 'Back to Quizzes', 'personality-assessment' ); ?></a>
			<hr class="wp-header-end">
			<form method="post">
				<input type="hidden" name="quiz_id" value="<?php echo esc_attr( $quiz_id ); ?>" />
				<?php wp_nonce_field( 'pa_save_quiz', 'pa_save_quiz_nonce' ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="title"><?php esc_html_e( 'Title', 'personality-assessment' ); ?></label>
							</th>
							<td>
								<input type="text" name="title" id="title" class="regular-text" value="<?php echo esc_attr( $quiz ? $quiz->title : '' ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="description"><?php esc_html_e( 'Description', 'personality-assessment' ); ?></label>
							</th>
							<td>
								<textarea name="description" id="description" rows="5" cols="50"><?php echo esc_textarea( $quiz ? $quiz->description : '' ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="status"><?php esc_html_e( 'Status', 'personality-assessment' ); ?></label>
							</th>
							<td>
								<select name="status" id="status">
									<option value="draft" <?php selected( $quiz ? $quiz->status : 'draft', 'draft' ); ?>><?php esc_html_e( 'Draft', 'personality-assessment' ); ?></option>
									<option value="published" <?php selected( $quiz ? $quiz->status : 'draft', 'published' ); ?>><?php esc_html_e( 'Published', 'personality-assessment' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="require_login"><?php esc_html_e( 'Requires Login', 'personality-assessment' ); ?></label>
							</th>
							<td>
								<input type="checkbox" name="require_login" id="require_login" value="1" <?php checked( $quiz ? $quiz->require_login : 1, 1 ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="webhook_url"><?php esc_html_e( 'Webhook URL', 'personality-assessment' ); ?></label>
							</th>
							<td>
								<input type="url" name="webhook_url" id="webhook_url" class="regular-text" value="<?php echo esc_attr( $quiz ? $quiz->webhook_url : '' ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="webhook_secret"><?php esc_html_e( 'Webhook Secret', 'personality-assessment' ); ?></label>
							</th>
							<td>
								<input type="text" name="webhook_secret" id="webhook_secret" class="regular-text" value="<?php echo esc_attr( $quiz ? $quiz->webhook_secret : '' ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="success_message"><?php esc_html_e( 'Success Message', 'personality-assessment' ); ?></label>
							</th>
							<td>
								<?php
								$content   = $quiz ? $quiz->success_message : '';
								$editor_id = 'success_message';
								$settings  = array( 'textarea_name' => 'success_message' );
								wp_editor( $content, $editor_id, $settings );
								?>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button( __( 'Save Quiz', 'personality-assessment' ) ); ?>
			</form>
			<?php if ( $quiz_id ) : ?>
				<div id="pa-quiz-editor-container">
					<div id="pa-quiz-editor-main">
						<div class="questions-header">
							<h2><?php esc_html_e( 'Questions', 'personality-assessment' ); ?></h2>
						</div>
						<div class="questions-list">
							<?php
							$questions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pa_questions WHERE quiz_id = %d ORDER BY position ASC", $quiz_id ) );
							if ( $questions ) {
								foreach ( $questions as $question ) {
									$this->render_question_item( $question );
								}
							}
							?>
						</div>
					</div>
					<div id="pa-quiz-editor-sidebar">
						<button class="button button-primary" id="add-question-button" data-quiz-id="<?php echo esc_attr( $quiz_id ); ?>">
							<?php esc_html_e( 'Add Question', 'personality-assessment' ); ?>
						</button>

						<div id="add-question-form-wrapper" style="display: none;">
							<form method="post" id="add-question-form" class="pa-sidebar-form">
								<input type="hidden" name="quiz_id" value="<?php echo esc_attr( $quiz_id ); ?>" />
								<?php wp_nonce_field( 'pa_save_question', 'pa_save_question_nonce' ); ?>
								<div class="form-field">
									<label for="question_title"><?php esc_html_e( 'Title', 'personality-assessment' ); ?></label>
									<input type="text" name="question_title" id="question_title" class="regular-text" />
								</div>
								<?php submit_button( __( 'Add Question', 'personality-assessment' ) ); ?>
							</form>
						</div>


						<div class="question-settings-wrapper"  style="display: none;">
							<form method="post" id="question-settings-form">
								<div class="settings-fields">
									<input type="hidden" name="question_id" id="setting_question_id" value="" />
									<input type="hidden" name="action" value="pa_save_question_details" />
									<?php wp_nonce_field( 'pa_save_question_details', 'pa_save_question_details_nonce' ); ?>

									<div class="form-field">
										<label for="setting_question_type"><?php esc_html_e( 'Question Type', 'personality-assessment' ); ?></label>
										<select name="question_type" id="setting_question_type">
											<option value="text"><?php esc_html_e( 'Text', 'personality-assessment' ); ?></option>
											<option value="single"><?php esc_html_e( 'Single Choice', 'personality-assessment' ); ?></option>
											<option value="multiple"><?php esc_html_e( 'Multiple Choice', 'personality-assessment' ); ?></option>
										</select>
									</div>
									<div class="form-field">
										<input type="checkbox" name="is_required" id="setting_is_required" value="1" />
										<label for="setting_is_required"><?php esc_html_e( 'Required', 'personality-assessment' ); ?></label>
									</div>

									<div class="form-actions">
										<a href="#" class="cancel-button"><?php esc_html_e( 'Cancel', 'personality-assessment' ); ?></a>
										<?php submit_button( __( 'Save Question', 'personality-assessment' ), 'primary', 'save-question-settings' ); ?>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the results page.
	 */
	public function render_results_page() {
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';

		switch ( $action ) {
			case 'view':
				$this->render_result_details_page();
				break;
			default:
				$this->render_results_list();
				break;
		}
	}

	/**
	 * Render the results list.
	 */
	public function render_results_list() {
		require_once PA_PLUGIN_DIR . 'admin/class-pa-results-list-table.php';
		$list_table = new Results_List_Table();
		$list_table->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Results', 'personality-assessment' ); ?></h1>
			<hr class="wp-header-end">

			<form method="post" action="">
				<input type="hidden" name="action" value="export_results_csv">
				<?php
				global $wpdb;
				$quizzes = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}pa_quizzes" );
				?>
				<select name="quiz_id">
					<option value=""><?php esc_html_e( 'Select a Quiz', 'personality-assessment' ); ?></option>
					<?php foreach ( $quizzes as $quiz ) : ?>
						<option value="<?php echo esc_attr( $quiz->id ); ?>"><?php echo esc_html( $quiz->title ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php submit_button( __( 'Export to CSV', 'personality-assessment' ), 'primary', 'export_results' ); ?>
			</form>

			<form method="post">
				<?php
				$list_table->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the result details page.
	 */
	public function render_result_details_page() {
		global $wpdb;

		$result_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT r.*, q.title as quiz_title, u.display_name as user_name
				FROM {$wpdb->prefix}pa_results r
				LEFT JOIN {$wpdb->prefix}pa_quizzes q ON r.quiz_id = q.id
				LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
				WHERE r.id = %d",
				$result_id
			)
		);
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Result Details', 'personality-assessment' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=pa-results' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to Results', 'personality-assessment' ); ?></a>
			<hr class="wp-header-end">
			<?php if ( ! $result ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'Invalid result specified.', 'personality-assessment' ); ?></p></div>
				<?php
				return;
			endif;
			?>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Quiz', 'personality-assessment' ); ?></th>
						<td><?php echo esc_html( $result->quiz_title ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'User', 'personality-assessment' ); ?></th>
						<td><?php echo $result->user_name ? esc_html( $result->user_name ) : esc_html__( 'Guest', 'personality-assessment' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Submitted At', 'personality-assessment' ); ?></th>
						<td><?php echo esc_html( $result->submitted_at ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Dominant Label', 'personality-assessment' ); ?></th>
						<td><?php echo esc_html( $result->dominant_label ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Total Score', 'personality-assessment' ); ?></th>
						<td><?php echo esc_html( $result->score_total ); ?></td>
					</tr>
				</tbody>
			</table>

			<h2 style="margin-top: 2rem;"><?php esc_html_e( 'Label Scores', 'personality-assessment' ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Label', 'personality-assessment' ); ?></th>
						<th><?php esc_html_e( 'Score', 'personality-assessment' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$label_scores = json_decode( $result->label_scores, true );
					if ( is_array( $label_scores ) ) {
						foreach ( $label_scores as $label => $score ) {
							?>
							<tr>
								<td><?php echo esc_html( $label ); ?></td>
								<td><?php echo esc_html( $score ); ?></td>
							</tr>
							<?php
						}
					}
					?>
				</tbody>
			</table>

			<h2 style="margin-top: 2rem;"><?php esc_html_e( 'Submitted Answers', 'personality-assessment' ); ?></h2>
			<div class="pa-result-answers">
				<?php
				$payload = json_decode( $result->raw_payload, true );
				$answers = isset( $payload['answers'] ) ? $payload['answers'] : array();

				if ( ! empty( $answers ) ) {
					foreach ( $answers as $answer_data ) {
						$question_id = absint( $answer_data['question_id'] );
						$question    = $wpdb->get_row( $wpdb->prepare( "SELECT title, type FROM {$wpdb->prefix}pa_questions WHERE id = %d", $question_id ) );

						if ( ! $question ) {
							continue;
						}
						?>
						<div class="postbox">
							<h3 class="hndle"><span><?php echo esc_html( $question->title ); ?></span></h3>
							<div class="inside">
								<?php
								switch ( $question->type ) {
									case 'text':
										echo '<blockquote>' . esc_textarea( $answer_data['value'] ) . '</blockquote>';
										break;
									case 'single':
									case 'multiple':
										if ( ! empty( $answer_data['selected'] ) ) {
											echo '<ul>';
											foreach ( $answer_data['selected'] as $selected_answer ) {
												printf(
													'<li>%s</li>',
													esc_html( $selected_answer['label'] )
												);
											}
											echo '</ul>';
										}
										break;
								}
								?>
							</div>
						</div>
						<?php
					}
				} else {
					?>
					<p><?php esc_html_e( 'No answers were submitted for this result.', 'personality-assessment' ); ?></p>
					<?php
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Settings', 'personality-assessment' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'personality-assessment' );
				do_settings_sections( 'personality-assessment' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Save the quiz.
	 */
	public function save_quiz() {
		if ( ! isset( $_POST['pa_save_quiz_nonce'] ) || ! wp_verify_nonce( $_POST['pa_save_quiz_nonce'], 'pa_save_quiz' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;

		$quiz_id = isset( $_POST['quiz_id'] ) ? intval( $_POST['quiz_id'] ) : 0;

		$data = array(
			'title'           => sanitize_text_field( $_POST['title'] ),
			'description'     => sanitize_textarea_field( $_POST['description'] ),
			'status'          => sanitize_text_field( $_POST['status'] ),
			'require_login'   => isset( $_POST['require_login'] ) ? 1 : 0,
			'webhook_url'     => sanitize_url( $_POST['webhook_url'] ),
			'webhook_secret'  => sanitize_text_field( $_POST['webhook_secret'] ),
			'success_message' => wp_kses_post( $_POST['success_message'] ),
		);

		if ( $quiz_id ) {
			$wpdb->update( "{$wpdb->prefix}pa_quizzes", $data, array( 'id' => $quiz_id ) );
		} else {
			$data['slug'] = sanitize_title( $data['title'] );
			$wpdb->insert( "{$wpdb->prefix}pa_quizzes", $data );
			$quiz_id = $wpdb->insert_id;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=personality-assessment&action=edit&id=' . $quiz_id . '&message=1' ) );
		exit;
	}

	/**
	 * Save the question.
	 */
	public function save_question() {
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( ! isset( $_POST['action'] ) || 'save_question' !== $_POST['action'] ) {
			return;
		}

		if ( ! isset( $_POST['pa_save_question_nonce'] ) || ! wp_verify_nonce( $_POST['pa_save_question_nonce'], 'pa_save_question' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;

		$question_id = isset( $_POST['question_id'] ) ? intval( $_POST['question_id'] ) : 0;
		$quiz_id     = isset( $_POST['quiz_id'] ) ? intval( $_POST['quiz_id'] ) : 0;

		$data = array(
			'title'       => sanitize_text_field( $_POST['question_title'] ),
			'type'        => sanitize_text_field( $_POST['question_type'] ),
			'is_required' => isset( $_POST['is_required'] ) ? 1 : 0,
		);

		if ( $question_id ) {
			$wpdb->update( "{$wpdb->prefix}pa_questions", $data, array( 'id' => $question_id ) );
			$quiz_id = $wpdb->get_var( $wpdb->prepare( "SELECT quiz_id FROM {$wpdb->prefix}pa_questions WHERE id = %d", $question_id ) );
		} else {
			$data['quiz_id'] = $quiz_id;
			$position        = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}pa_questions WHERE quiz_id = %d", $quiz_id ) );
			$data['position'] = $position + 1;
			$wpdb->insert( "{$wpdb->prefix}pa_questions", $data );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=personality-assessment&action=edit&id=' . $quiz_id ) );
		exit;
	}

	/**
	 * Delete a question.
	 */
	public function delete_question() {
		if ( ! isset( $_GET['action'] ) || 'delete_question' !== $_GET['action'] ) {
			return;
		}

		if ( ! isset( $_GET['question_id'] ) || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'pa_delete_question' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;

		$question_id = intval( $_GET['question_id'] );
		$quiz_id     = $wpdb->get_var( $wpdb->prepare( "SELECT quiz_id FROM {$wpdb->prefix}pa_questions WHERE id = %d", $question_id ) );

		$wpdb->delete( "{$wpdb->prefix}pa_questions", array( 'id' => $question_id ) );
		$wpdb->delete( "{$wpdb->prefix}pa_answers", array( 'question_id' => $question_id ) );

		wp_safe_redirect( admin_url( 'admin.php?page=personality-assessment&action=edit&id=' . $quiz_id ) );
		exit;
	}

	/**
	 * Delete an answer.
	 */
	public function delete_answer() {
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( ! isset( $_GET['action'] ) || 'delete_answer' !== $_GET['action'] ) {
			return;
		}

		if ( ! isset( $_GET['answer_id'] ) || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'pa_delete_answer' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;

		$answer_id   = intval( $_GET['answer_id'] );
		$question_id = $wpdb->get_var( $wpdb->prepare( "SELECT question_id FROM {$wpdb->prefix}pa_answers WHERE id = %d", $answer_id ) );
		$quiz_id     = $wpdb->get_var( $wpdb->prepare( "SELECT quiz_id FROM {$wpdb->prefix}pa_questions WHERE id = %d", $question_id ) );

		$wpdb->delete( "{$wpdb->prefix}pa_answers", array( 'id' => $answer_id ) );

		wp_safe_redirect( admin_url( 'admin.php?page=personality-assessment&action=edit&id=' . $quiz_id ) );
		exit;
	}

	/**
	 * Save the answer.
	 */
	public function save_answer() {
		if ( ! isset( $_POST['action'] ) || 'save_answer' !== $_POST['action'] ) {
			return;
		}

		if ( wp_doing_ajax() ) {
			return;
		}

		if ( ! isset( $_POST['pa_save_answer_nonce'] ) || ! wp_verify_nonce( $_POST['pa_save_answer_nonce'], 'pa_save_answer' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;

		$answer_id   = isset( $_POST['answer_id'] ) ? intval( $_POST['answer_id'] ) : 0;
		$question_id = isset( $_POST['question_id'] ) ? intval( $_POST['question_id'] ) : 0;

		$data = array(
			'label'             => sanitize_text_field( $_POST['answer_label'] ),
			'weight'            => floatval( $_POST['answer_weight'] ),
			'personality_label' => sanitize_text_field( $_POST['personality_label'] ),
		);

		if ( $answer_id ) {
			$wpdb->update( "{$wpdb->prefix}pa_answers", $data, array( 'id' => $answer_id ) );
			$question_id = $wpdb->get_var( $wpdb->prepare( "SELECT question_id FROM {$wpdb->prefix}pa_answers WHERE id = %d", $answer_id ) );
		} else {
			$data['question_id'] = $question_id;
			$position            = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}pa_answers WHERE question_id = %d", $question_id ) );
			$data['position']    = $position + 1;
			$wpdb->insert( "{$wpdb->prefix}pa_answers", $data );
		}

		$quiz_id = $wpdb->get_var( $wpdb->prepare( "SELECT quiz_id FROM {$wpdb->prefix}pa_questions WHERE id = %d", $question_id ) );

		wp_safe_redirect( admin_url( 'admin.php?page=personality-assessment&action=edit&id=' . $quiz_id ) );
		exit;
	}
}
