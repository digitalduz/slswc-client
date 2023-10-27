<?php
/**
 * Main plugin file.
 *
 * @version     1.1.0
 * @since       1.1.0
 * @package     SLSWC_Updater
 * @link        https://licenseserver.io/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Plugin Name:       Software Updater (Software License Server for WooCommerce)
 * Plugin URI:        https://licenseserver.io/
 * Description:       Manage updates for your plugins and themes sold using the License Server for WooCommerce plugin
 * Version:           1.1.0
 * Author:            MadVault LLC
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       slswc-client
 * Domain Path:       /languages
 *
 * Requires at least:    5.0.0
 * Tested up to:         6.3.2
 *
 * SLSWC:                   plugin
 * SLSWC Slug:              slswc-client
 * SLSWC Documentation URL: https://licenseserver.io/documentation
 */

require __DIR__ . '/vendor/autoload.php';

use Madvault\Slswc\Client\Updater\PluginBootstrap;

define( 'SLSWC_CLIENT_VERSION', '1.1.0' );

define( 'SLSWC_CLIENT_FILE', __FILE__ );
define( 'SLSWC_CLIENT_PATH', plugin_dir_path( __FILE__ ) );
define( 'SLSWC_CLIENT_PARTIALS_DIR', SLSWC_CLIENT_PATH . 'partials/' );
define( 'SLSWC_CLIENT_ASSETS_URL', plugin_dir_url( __FILE__ ) . 'assets/' );
define( 'SLSWC_CLIENT_LOGGING', true );
define( 'SLSWC_CLIENT_SERVER_URL', 'http://licenseserver.io/' ); // Replace this with the license server url.

$slswc_updater_plugin = PluginBootstrap::get_instance();
$slswc_updater_plugin->run();
