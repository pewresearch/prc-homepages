<?php
namespace PRC\Platform\Homepages;

/**
 * PRC Homepages
 *
 * @package           PRC_Homepages
 * @author            Seth Rubenstein
 * @copyright         2024 Pew Research Center
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       PRC Homepages
 * Plugin URI:        https://github.com/pewresearch/prc-homepages
 * Description:       A custom content type for managing dynamic website homepages with native WordPress scheduling and preview capabilities. Enables editors to create, schedule, and preview homepage versions with full revision history, ensuring seamless content transitions. Perfect for organizations that need to coordinate homepage updates or maintain multiple versions of their front page content.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      8.2
 * Author:            Seth Rubenstein
 * Author URI:        https://pewresearch.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       prc-homepages
 * Requires Plugins:  prc-platform-core
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PRC_HOMEPAGES_FILE', __FILE__ );
define( 'PRC_HOMEPAGES_DIR', __DIR__ );
define( 'PRC_HOMEPAGES_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-prc-x-x-x-activator.php
 */
function activate_plugin() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-plugin-activator.php';
	Plugin_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-prc-x-x-x-deactivator.php
 */
function deactivate_plugin() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-plugin-deactivator.php';
	Plugin_Deactivator::deactivate();
}

register_activation_hook( __FILE__, '\PRC\Platform\Homepages\activate_plugin' );
register_deactivation_hook( __FILE__, '\PRC\Platform\Homepages\deactivate_plugin' );

/**
 * The core plugin class that is used to define the hooks that initialize the various components.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-plugin.php';

/**
 * Helper utilities
 */
require plugin_dir_path( __FILE__ ) . 'includes/utils.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_prc_homepages() {
	$plugin = new Plugin();
	$plugin->run();
}
run_prc_homepages();
