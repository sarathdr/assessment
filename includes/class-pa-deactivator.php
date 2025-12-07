<?php
/**
 * Deactivator
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
 * Fired during plugin deactivation
 */
class Deactivator
{
	/**
	 * Run the deactivator
	 */
	public static function deactivate()
	{
		// No custom deactivation logic needed for now.
	}
}
