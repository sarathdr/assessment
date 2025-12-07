<?php
/**
 * Plugin Name:       Personality Assessment
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       Create and manage personality quizzes with weighted answers.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * @author            Sarath
 * @copyright         2025 Drizzle limited
 * @license           Contact: sarath@drizzle.media
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       personality-assessment
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

define('PA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PA_PLUGIN_VERSION', '1.0.0');

// Include the autoloader.
spl_autoload_register(
	function ($class) {
		$prefix_map = array(
			'PA\\Admin\\' => __DIR__ . '/admin/',
			'PA\\Public\\' => __DIR__ . '/public/',
			'PA\\' => __DIR__ . '/includes/',
		);

		foreach ($prefix_map as $prefix => $base_dir) {
			$len = strlen($prefix);
			if (strncmp($prefix, $class, $len) !== 0) {
				continue;
			}

			$relative_class = substr($class, $len);
			$file = $base_dir . 'class-pa-' . str_replace('_', '-', strtolower($relative_class)) . '.php';

			if (file_exists($file)) {
				require $file;
				return;
			}
		}
	}
);

/**
 * The code that runs during plugin activation.
 */
function activate_personality_assessment()
{
	require_once PA_PLUGIN_DIR . 'includes/class-pa-installer.php';
	PA\Installer::install();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_personality_assessment()
{
	require_once PA_PLUGIN_DIR . 'includes/class-pa-deactivator.php';
	PA\Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_personality_assessment');
register_deactivation_hook(__FILE__, 'deactivate_personality_assessment');

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_personality_assessment()
{
	new PA\Taxonomies();
	new PA\Admin\Admin();
	new PA\Admin\Labels();
	new PA\Admin\Settings();
	new PA\Public\Shortcode();
	new PA\REST_API();
	new PA\Block();
}

add_action('plugins_loaded', 'run_personality_assessment');
