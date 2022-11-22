<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           IBC
 *
 * @wordpress-plugin
 * Plugin Name:       Ingenius Below Content
 * Plugin URI:        http://example.com/ibc-uri/
 * Description:       Ingenius plugin that allow below content on attributes and tags.
 * Version:           1.0.3
 * Author:            Ingenius
 * Author URI:        https://ingenius.agency/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ibc
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('IBC_VERSION', '1.0.3');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ibc-activator.php
 */
function activate_IBC() {
	require_once plugin_dir_path(__FILE__) . 'includes/class-ibc-activator.php';
	IBC_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ibc-deactivator.php
 */
function deactivate_IBC() {
	require_once plugin_dir_path(__FILE__) . 'includes/class-ibc-deactivator.php';
	IBC_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_IBC');
register_deactivation_hook(__FILE__, 'deactivate_IBC');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-ibc.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_IBC() {

	$plugin = new IBC();
	$plugin->run();
}
run_IBC();

require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/martinfeuillet/ingenius-below-content',
	__FILE__,
	'ingenius-below-content'
);


//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('master');
