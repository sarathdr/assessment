<?php
/**
 * Labels
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
 * The admin-specific functionality for labels.
 */
class Labels {

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
		add_action( 'pa_personality_label_add_form_fields', array( $this, 'add_term_fields' ) );
		add_action( 'pa_personality_label_edit_form_fields', array( $this, 'edit_term_fields' ) );
		add_action( 'created_pa_personality_label', array( $this, 'save_term_meta' ) );
		add_action( 'edited_pa_personality_label', array( $this, 'save_term_meta' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add custom fields to the add term form.
	 */
	public function add_term_fields() {
		?>
		<div class="form-field">
			<label for="term-color"><?php esc_html_e( 'Color', 'personality-assessment' ); ?></label>
			<input type="text" name="term_color" id="term-color" class="pa-color-picker" value="#ffffff">
		</div>
		<div class="form-field">
			<label for="term-description"><?php esc_html_e( 'Description', 'personality-assessment' ); ?></label>
			<textarea name="term_description" id="term-description" rows="5" cols="40"></textarea>
		</div>
		<?php
	}

	/**
	 * Add custom fields to the edit term form.
	 */
	public function edit_term_fields( $term ) {
		$color       = get_term_meta( $term->term_id, 'color', true );
		$description = get_term_meta( $term->term_id, 'description', true );
		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="term-color"><?php esc_html_e( 'Color', 'personality-assessment' ); ?></label>
			</th>
			<td>
				<input type="text" name="term_color" id="term-color" class="pa-color-picker" value="<?php echo esc_attr( $color ); ?>">
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="term-description"><?php esc_html_e( 'Description', 'personality-assessment' ); ?></label>
			</th>
			<td>
				<textarea name="term_description" id="term-description" rows="5" cols="50"><?php echo esc_textarea( $description ); ?></textarea>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save the custom term meta.
	 */
	public function save_term_meta( $term_id ) {
		if ( isset( $_POST['term_color'] ) ) {
			update_term_meta( $term_id, 'color', sanitize_hex_color( $_POST['term_color'] ) );
		}
		if ( isset( $_POST['term_description'] ) ) {
			update_term_meta( $term_id, 'description', sanitize_textarea_field( $_POST['term_description'] ) );
		}
	}

	/**
	 * Enqueue the color picker.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'term.php' !== $hook && 'edit-tags.php' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'pa-admin-script', PA_PLUGIN_URL . 'assets/js/admin.js', array( 'wp-color-picker' ), PA_PLUGIN_VERSION, true );
	}
}
