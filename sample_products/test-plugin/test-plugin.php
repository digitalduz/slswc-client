<?php
/**
 * Software License Server for WooCommerce
 *
 * @package Test_Plugin
 * @author  Red Panda Ventures
 * @version 1.0.0
 * @since   1.0.0
 */

/**
 * Plugin Name: Test Plugin
 * Plugin URI: https://wcvendors.com/plugins
 * Description: Basic WordPress plugin to test Software License Server for WooCommerce
 * Text Domain: test-plugin
 * Author URI: https://wcvendors.com
 * License: https://www.gnu.org/licenses/gpl-2.0.html
 * Version: 1.0.0
 * Author: Red Panda Ventures
 * Domain Path: /languages
 * SLSWC: plugin
 * Slug: test-plugin
 * Documentation URL: https://www.gnu.org/licenses/gpl-2.0.html
 * Required WP: 5.1
 * Compatible To: 5.2.1
 */
function slswc_client() {
	require_once dirname( __FILE__ ) . '/class-wc-software-license-client.php';
	return WC_Software_License_Client::get_instance( 'http://example.com/', __FILE__, 'plugin' );
}
add_action( 'plugins_loaded', 'slswc_client', 11 );
