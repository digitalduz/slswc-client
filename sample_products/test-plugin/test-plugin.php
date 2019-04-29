<?php

/**
 * Plugin Name: Test Plugin
 * Plugin URI: https://wcvendors.com/plugins
 * Description: Basic WordPress plugin to test Software License Server for WooCommerce
 * Text Domain: testplugin
 * Author URI: https://wcvendors.com
 * License: https://www.gnu.org/licenses/gpl-2.0.html
 * Version: 1.0.1
 * Author: Red Panda Ventures
 * Domain Path: /languages
 * SLSWC: plugin
 * Slug: testplugin
 * Documentation URL: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires WP: 5.1
 * Tested WP: 5.1
 */
function slswc_client() {
	require_once '../class-wc-software-license-client.php';
	return WC_Software_License_Client::get_instance( 'http://slswc.test/', __FILE__, 'plugin' );
}

slswc_client();
