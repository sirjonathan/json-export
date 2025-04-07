<?php
/**
 * Plugin Name: JSON Export
 * Plugin URI: https://github.com/jonathanwold/json-export
 * Description: Exports WordPress posts as JSON, with formatting options for use with LLMs.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Tested up to: 6.7
 * Author: Jonathan Wold
 * Author URI: https://jonathanwold.com
 * Text Domain: json-export
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package JSONExport
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define our plugin constants.
define( 'JSON_EXPORT_PATH', plugin_dir_path( __FILE__ ) );
define( 'JSON_EXPORT_URL', plugin_dir_url( __FILE__ ) );
define( 'JSON_EXPORT_TEXT_DOMAIN', 'json-export' );
define( 'JSON_EXPORT_BASENAME', plugin_basename( __FILE__ ) );
define( 'JSON_EXPORT_VERSION', '1.0.0' );

// Include Composer autoload.
require_once JSON_EXPORT_PATH . 'vendor/autoload.php';

/**
 * Initialize the plugin.
 */
function json_export_init(): void {
	\JSONExport\PHP\JSONExport::get_instance();
}

// Hook into plugins_loaded action.
add_action( 'plugins_loaded', 'json_export_init' );
