<?php
/**
 * Block
 *
 * @package           PersonalityAssessment
 * @author            Jules
 * @copyright         2024 Jules
 * @license           GPL-3.0-or-later
 *
 * @wordpress-plugin
 */

namespace PA;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The block functionality of the plugin.
 */
class Block {

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register the Gutenberg blocks.
	 */
	public function register_blocks() {
		register_block_type(
			PA_PLUGIN_DIR . 'blocks/personality-quiz',
			array(
				'render_callback' => 'pa_render_quiz_block',
			)
		);
	}
}
