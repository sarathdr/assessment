<?php
/**
 * Settings
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
 * The admin-specific functionality for settings.
 */
class Settings {

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register the settings.
	 */
	public function register_settings() {
		register_setting(
			'personality-assessment',
			'pa_settings',
			array( $this, 'sanitize' )
		);

		add_settings_section(
			'pa_general_section',
			__( 'General Settings', 'personality-assessment' ),
			null,
			'personality-assessment'
		);

		add_settings_field(
			'require_login_default',
			__( 'Require Login by Default', 'personality-assessment' ),
			array( $this, 'render_require_login_default_field' ),
			'personality-assessment',
			'pa_general_section'
		);

		add_settings_field(
			'single_attempt_default',
			__( 'Single Attempt by Default', 'personality-assessment' ),
			array( $this, 'render_single_attempt_default_field' ),
			'personality-assessment',
			'pa_general_section'
		);
	}

	/**
	 * Sanitize the settings.
	 */
	public function sanitize( $input ) {
		$new_input = array();

		if ( isset( $input['require_login_default'] ) ) {
			$new_input['require_login_default'] = absint( $input['require_login_default'] );
		}

		if ( isset( $input['single_attempt_default'] ) ) {
			$new_input['single_attempt_default'] = absint( $input['single_attempt_default'] );
		}

		return $new_input;
	}

	/**
	 * Render the require login default field.
	 */
	public function render_require_login_default_field() {
		$options = get_option( 'pa_settings' );
		?>
		<input type="checkbox" name="pa_settings[require_login_default]" value="1" <?php checked( isset( $options['require_login_default'] ) ? $options['require_login_default'] : 0, 1 ); ?> />
		<?php
	}

	/**
	 * Render the single attempt default field.
	 */
	public function render_single_attempt_default_field() {
		$options = get_option( 'pa_settings' );
		?>
		<input type="checkbox" name="pa_settings[single_attempt_default]" value="1" <?php checked( isset( $options['single_attempt_default'] ) ? $options['single_attempt_default'] : 0, 1 ); ?> />
		<?php
	}
}
