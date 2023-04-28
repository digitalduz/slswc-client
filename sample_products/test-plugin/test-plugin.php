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
 * Requires at least : 5.7
 *
 * SLSWC                   : plugin
 * SLSWC Slug              : test-plugin
 * SLSWC Documentation URL : https://www.gnu.org/licenses/gpl-2.0.html
 * SLSWC Compatible To     : 5.8.1
 */

/**
 * First you must install the license server client library using composer.
 *
 * Install using: composer require madvault/slswc-client
 */
require dirname( __FILE__ ) . '/vendor/autoload.php';

use Madvault\Slswc\Client\SLSWC_Client;

/**
 * Function to initialize the client.
 *
 * @return object
 */
function your_prefix_slswc_client() {
	return SLSWC_Client::get_instance( 'http://example.com/', __FILE__, 'plugin' );
}
add_action( 'plugins_loaded', 'slswc_client', 11 );
