<?php
/**
 * The Software License Server for WooCommerce Client Library
 *
 * This class defines all code necessary to check for a valid license and available updates stored on your Software License Server for WooCommerce.
 *
 * Documentation can be found here : https://licenseserver.io/documentation
 *
 * To integrate this into your software product include the following code in your MAIN plugin file, do not attempt.
 * to add this code in any other file but your main plugin file.
 * 
 * Required Parameters.
 *
 *      @param string  required $license_server_url - The url to the license server.
 *      @param string  required $plugin_file - The path to the main plugin file.
 *
 * Optional Parameters.
 *      @param string  optional $product_type - The type of product. plugin/theme
 *
 *  require_once 'vendor/autoload.php';
 *  use Madvault\Slswc\Client\Client;
 *
 *  function your_prefix_slswc_instance(){
 *      return Client::get_instance( 'http://yourshopurl.here.com', $plugin_file, $product_type );
 *  } // slswc_instance()
 *
 *  your_prefix_slswc_instance();
 * 
 * All plugins and themes must have the following file headers in order to be used by the client:
 * SLSWC                   (required) The type of product this is (theme/plugin). This is also required by the client to filter plugins/themes updated by the client.
 * SLSWC Slug              (required) The plugin or theme slug. It must be the same as the slug of the WooCommerce product selling the theme/plugin.
 * SLSWC Documentation URL (optional) The link to the plugin/theme's documentation.
 * SLSWC Compatible To     (optional) The maximum version of wordpress the plugin/theme is compatible with.
 * 
 * And the optional WordPress header:
 * Requires at least:      (optional) The minimum WordPress version required by the plugin or theme.
 *
 * @version     1.1.0
 * @since       1.1.0
 * @package     SLSWC_Client
 * @link        https://licenseserver.io/
 */
/**
 * Plugin Name:       Software License Server Client
 * Plugin URI:        https://licenseserver.io/
 * Description:       Manage updates for your plugins and themes sold using the License Server for WooCommerce plugin
 * Documentation URL: https://licenseserver.io/documentation
 * Version:           1.1.0
 */

namespace Madvault\Slswc\Client;

require __DIR__ . '/vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Madvault\Slswc\Client\Updater;
use Madvault\Slswc\Client\ClientManager;
use Madvault\Slswc\Client\Client;

define ( 'SLSWC_CLIENT_VERSION', '1.1.0' );

define( 'SLSWC_CLIENT_FILE', __FILE__ );
define( 'SLSWC_CLIENT_PATH', plugin_dir_path( __FILE__ ) );
define( 'SLSWC_CLIENT_PARTIALS_DIR', SLSWC_CLIENT_PATH . '/partials/' );
define( 'SLSWC_CLIENT_ASSETS_URL', plugin_dir_url( __FILE__ ) . '/assets/' );
define( 'SLSWC_CLIENT_LOGGING', false );

add_action( 'plugins_loaded', function () {
	global $slswc_updater;

	$license_key = '';

	$slswc_client = Client::get_instance( 'http://slswc.local', __FILE__, 'plugin' );
	$slswc_client->init_hooks();

	$slswc_updater = new Updater( __FILE__, SLSWC_CLIENT_VERSION );
	$slswc_updater->init_hooks();
});

function slswc_client_manager () {
	$client_manager = ClientManager::get_instance();
	$client_manager->init_hooks();

	return $client_manager;
}

slswc_client_manager();

