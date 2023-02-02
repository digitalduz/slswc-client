<?php
/**
 * Software License Server for WooCommerce
 *
 * @package Test_Plugin
 * @author  License Server
 * @version 1.0.0
 * @since   1.0.0
 */

/**
 * Plugin Name: Test Plugin
 * Plugin URI: https://testplugin.com/plugins
 * Description: Basic WordPress plugin to test Software License Server for WooCommerce
 * Text Domain: test-plugin
 * Author URI: https://licenseserver.io
 * License: https://www.gnu.org/licenses/gpl-2.0.html
 * Version: 1.0.0
 * Author: License Server 
 * Domain Path: /languages
 * SLSWC: plugin
 * SLSWC Slug: test-plugin
 * SLSWC Documentation URL: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires WP: 5.7
 * SLSWC Compatible To: 5.8.1
 */
function slswc_client() {
	require_once dirname( __FILE__ ) . '/class-slswc-client.php';
	return SLSWC_Client::get_instance( 'http://example.com/', __FILE__, 'plugin' );
}
add_action( 'plugins_loaded', 'slswc_client', 11 );
