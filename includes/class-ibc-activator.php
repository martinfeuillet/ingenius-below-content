<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    IBC
 * @subpackage IBC/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    IBC
 * @subpackage IBC/includes
 * @author     Your Name <email@example.com>
 */
class IBC_Activator {


	public static function activate() {
		// if woocommerce is not active, deactivate the plugin
		if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			deactivate_plugins(plugin_basename(__FILE__));
			wp_die('Sorry, but this plugin requires the Woocommerce plugin to be installed and active. <br><a href="' . admin_url('plugins.php') . '">&laquo; Return to Plugins</a>');
		}
	}
}
