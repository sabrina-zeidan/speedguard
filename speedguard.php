<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin. * Network:       true
 *
 * @link              http://sabrinazeidan.com/
 * @since             1.0.0
 * @package           Speedguard
 *
 * @wordpress-plugin
 * Plugin Name:       SpeedGuard
 * Plugin URI:		  http://wordpress.org/plugins/speedguard/
 * Description:       Monitors load time of 65 most important pages of your website. Every single day. For free.
 * Version:           1.2
 * Author:            Sabrina Zeidan
 * Author URI:        http://sabrinazeidan.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       speedguard
 * Domain Path:       /languages
 * Network:       true
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently pligin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'SpeedGuard', '1.2' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-speedguard-activator.php
 */
function activate_speedguard() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-speedguard-activator.php';
	Speedguard_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-speedguard-deactivator.php
 */
function deactivate_speedguard() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-speedguard-deactivator.php';
	Speedguard_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_speedguard' );
register_deactivation_hook( __FILE__, 'deactivate_speedguard' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-speedguard.php';
 
/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_speedguard() {

	$plugin = new Speedguard();
	$plugin->run();

}
run_speedguard();
