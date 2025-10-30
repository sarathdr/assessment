<?php
/**
 * Deactivator
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
 * Fired during plugin deactivation
 */
class Deactivator {
	/**
	 * Run the deactivator
	 */
	public static function deactivate() {
		// No custom deactivation logic needed for now.
	}
}
