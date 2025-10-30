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
			case 'new':
			case 'edit':
				$this->render_quiz_form();
				break;
			case 'edit_question':
				$this->render_question_form();
				break;
			case 'edit_answer':
				$this->render_answer_form();
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
				<?php
				if ( $quiz_id ) {
					esc_html_e( 'Edit Quiz', 'personality-assessment' );
				} else {
					esc_html_e( 'Add New Quiz', 'personality-assessment' );
				}
				?>
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
					</tbody>
				</table>
				<?php submit_button( __( 'Save Quiz', 'personality-assessment' ) ); ?>
			</form>
			<?php if ( $quiz_id ) : ?>
				<div id="questions-section">
					<h2><?php esc_html_e( 'Questions', 'personality-assessment' ); ?></h2>
					<?php
					$questions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pa_questions WHERE quiz_id = %d ORDER BY position ASC", $quiz_id ) );
					if ( $questions ) {
						foreach ( $questions as $question ) {
							?>
							<div class="question-item">
								<h3><?php echo esc_html( $question->title ); ?></h3>
								<p><strong><?php esc_html_e( 'Type:', 'personality-assessment' ); ?></strong> <?php echo esc_html( $question->type ); ?></p>
								<p><strong><?php esc_html_e( 'Required:', 'personality-assessment' ); ?></strong> <?php echo $question->is_required ? esc_html__( 'Yes', 'personality-assessment' ) : esc_html__( 'No', 'personality-assessment' ); ?></p>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=personality-assessment&action=edit_question&question_id=' . $question->id ) ); ?>" class="edit-question"><?php esc_html_e( 'Edit', 'personality-assessment' ); ?></a>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=personality-assessment&action=delete_question&question_id=' . $question->id ), 'pa_delete_question' ) ); ?>" class="delete-question"><?php esc_html_e( 'Delete', 'personality-assessment' ); ?></a>

								<div class="answers-section">
									<h4><?php esc_html_e( 'Answers', 'personality-assessment' ); ?></h4>
									<?php
									$answers = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pa_answers WHERE question_id = %d ORDER BY position ASC", $question->id ) );
									if ( $answers ) {
										foreach ( $answers as $answer ) {
											?>
											<div class="answer-item">
												<p><?php echo esc_html( $answer->label ); ?></p>
												<p><strong><?php esc_html_e( 'Weight:', 'personality-assessment' ); ?></strong> <?php echo esc_html( $answer->weight ); ?></p>
												<p><strong><?php esc_html_e( 'Label:', 'personality-assessment' ); ?></strong> <?php echo esc_html( $answer->personality_label ); ?></p>
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=personality-assessment&action=edit_answer&answer_id=' . $answer->id ) ); ?>" class="edit-answer"><?php esc_html_e( 'Edit', 'personality-assessment' ); ?></a>
												<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=personality-assessment&action=delete_answer&answer_id=' . $answer->id ), 'pa_delete_answer' ) ); ?>" class="delete-answer"><?php esc_html_e( 'Delete', 'personality-assessment' ); ?></a>
											</div>
											<?php
										}
									}
									?>
									<form method="post">
										<input type="hidden" name="question_id" value="<?php echo esc_attr( $question->id ); ?>" />
										<input type="hidden" name="action" value="save_answer" />
										<?php wp_nonce_field( 'pa_save_answer', 'pa_save_answer_nonce' ); ?>
										<table class="form-table">
											<tbody>
												<tr>
													<th scope="row">
														<label for="answer_label"><?php esc_html_e( 'Label', 'personality-assessment' ); ?></label>
													</th>
													<td>
														<input type="text" name="answer_label" id="answer_label" class="regular-text" />
													</td>
												</tr>
												<tr>
													<th scope="row">
														<label for="answer_weight"><?php esc_html_e( 'Weight', 'personality-assessment' ); ?></label>
													</th>
													<td>
														<input type="number" name="answer_weight" id="answer_weight" class="small-text" step="0.1" />
													</td>
												</tr>
												<tr>
													<th scope="row">
														<label for="personality_label"><?php esc_html_e( 'Personality Label', 'personality-assessment' ); ?></label>
													</th>
													<td>
														<input type="text" name="personality_label" id="personality_label" class="regular-text" />
													</td>
												</tr>
											</tbody>
										</table>
										<?php submit_button( __( 'Add Answer', 'personality-assessment' ) ); ?>
									</form>
								</div>
							</div>
							<?php
						}
					}
					?>
					<hr>
					<h3><?php esc_html_e( 'Add New Question', 'personality-assessment' ); ?></h3>
					<form method="post">
						<input type="hidden" name="quiz_id" value="<?php echo esc_attr( $quiz_id ); ?>" />
						<input type="hidden" name="action" value="save_question" />
						<?php wp_nonce_field( 'pa_save_question', 'pa_save_question_nonce' ); ?>
						<table class="form-table">
							<tbody>
								<tr>
									<th scope="row">
										<label for="question_title"><?php esc_html_e( 'Title', 'personality-assessment' ); ?></label>
									</th>
									<td>
										<input type="text" name="question_title" id="question_title" class="regular-text" />
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="question_type"><?php esc_html_e( 'Type', 'personality-assessment' ); ?></label>
									</th>
									<td>
										<select name="question_type" id="question_type">
											<option value="text"><?php esc_html_e( 'Text', 'personality-assessment' ); ?></option>
											<option value="single"><?php esc_html_e( 'Single Choice', 'personality-assessment' ); ?></option>
											<option value="multiple"><?php esc_html_e( 'Multiple Choice', 'personality-assessment' ); ?></option>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="is_required"><?php esc_html_e( 'Required', 'personality-assessment' ); ?></label>
									</th>
									<td>
										<input type="checkbox" name="is_required" id="is_required" value="1" checked />
									</td>
								</tr>
							</tbody>
						</table>
						<?php submit_button( __( 'Add Question', 'personality-assessment' ) ); ?>
					</form>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the results page.
	 */
	public function render_results_page() {
		require_once PA_PLUGIN_DIR . 'admin/class-pa-results-list-table.php';
		$list_table = new Results_List_Table();
		$list_table->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Results', 'personality-assessment' ); ?></h1>
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
			'title'          => sanitize_text_field( $_POST['title'] ),
			'description'    => sanitize_textarea_field( $_POST['description'] ),
			'status'         => sanitize_text_field( $_POST['status'] ),
			'require_login'  => isset( $_POST['require_login'] ) ? 1 : 0,
			'webhook_url'    => sanitize_url( $_POST['webhook_url'] ),
			'webhook_secret' => sanitize_text_field( $_POST['webhook_secret'] ),
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

	/**
	 * Render the question form.
	 */
	public function render_question_form() {
		global $wpdb;

		$question_id = isset( $_GET['question_id'] ) ? intval( $_GET['question_id'] ) : 0;
		$question    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pa_questions WHERE id = %d", $question_id ) );
		$quiz_id     = $question->quiz_id;

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Edit Question', 'personality-assessment' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=personality-assessment&action=edit&id=' . $quiz_id ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to Quiz', 'personality-assessment' ); ?></a>
			<hr class="wp-header-end">
			<form method="post">
				<input type="hidden" name="question_id" value="<?php echo esc_attr( $question_id ); ?>" />
				<input type="hidden" name="action" value="save_question" />
				<?php wp_nonce_field( 'pa_save_question', 'pa_save_question_nonce' ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="question_title"><?php esc_html_e( 'Title', 'personality-assessment' ); ?></label>
							</th>
							<td>
								<input type="text" name="question_title" id="question_title" class="regular-text" value="<?php echo esc_attr( $question->title ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="question_type"><?php esc_html_e( 'Type', 'personality-assessment' ); ?></label>
							</th>
							<td>
								<select name="question_type" id="question_type">
									<option value="text" <?php selected( $question->type, 'text' ); ?>><?php esc_html_e( 'Text', 'personality-assessment' ); ?></option>
									<option value="single" <?php selected( $question->type, 'single' ); ?>><?php esc_html_e( 'Single Choice', 'personality-assessment' ); ?></option>
									<option value="multiple" <?php selected( $question->type, 'multiple' ); ?>><?php esc_html_e( 'Multiple Choice', 'personality-assessment' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="is_required"><?php esc_html_e( 'Required', 'personality-assessment' ); ?></label>
							</th>
							<td>
								<input type="checkbox" name="is_required" id="is_required" value="1" <?php checked( $question->is_required, 1 ); ?> />
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button( __( 'Save Question', 'personality-assessment' ) ); ?>
			</form>
		</div>
		<?php
	}
}
