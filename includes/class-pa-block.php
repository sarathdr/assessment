<?php
/**
 * Block
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
 * The block functionality of the plugin.
 */
class Block
{

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct()
	{
		add_action('init', array($this, 'register_blocks'));
	}

	/**
	 * Register the Gutenberg blocks.
	 */
	public function register_blocks()
	{
		register_block_type(
			PA_PLUGIN_DIR . 'blocks/personality-quiz',
			array(
				'render_callback' => 'pa_render_quiz_block',
			)
		);
	}
}
