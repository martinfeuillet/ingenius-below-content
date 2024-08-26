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
        if ( is_multisite() ) {
            // Include the necessary file to use is_plugin_active() and is_plugin_active_for_network()
            if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
                require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
            }

            // Check if WooCommerce is active on the network
            if ( ! is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {
                deactivate_plugins( plugin_basename( __FILE__ ) );
                wp_die( 'Sorry, but this plugin requires the WooCommerce plugin to be installed and active network-wide in a multisite environment. <br><a href="' . network_admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>' );
            }
        } else {
            // For a standard WordPress setup (single site)
            if ( ! in_array( 'woocommerce/woocommerce.php' , apply_filters( 'active_plugins' , get_option( 'active_plugins' ) ) ) ) {
                deactivate_plugins( plugin_basename( __FILE__ ) );
                wp_die( 'Sorry, but this plugin requires the WooCommerce plugin to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>' );
            }
        }
    }

}
