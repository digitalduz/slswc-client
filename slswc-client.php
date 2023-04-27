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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require __DIR__ . '/vendor/autoload.php';

define ( 'SLSWC_CLIENT_VERSION', '1.1.0' );

/**
 * Class responsible for initializing the client's core features.
 *
 * @version 1.1.0
 * @since   1.1.0
 */
class SLSWC_Client {
	/**
	 * Instance of this class
	 *
	 * @var SLSWC_Client
	 */
	public $instance = null;

	/**
	 * Whether to initialize the client manager or not
	 *
	 * @var boolean
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public $initialize_manager = false;

	/**
	 * Construct an instance of this class
	 *
	 * @param boolean $initialize_manager Whether to initialize ClientManager or not.
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public function __construct( $initialize_manager = false ) {
		$this->initialize_manager = $initialize_manager;
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public function init_hooks() {		
		add_action( 'admin_footer', array( $this, 'client_admin_script' ), 11 );

		if ( $this->initialize_manager ) {
			add_action( 'admin_init', array( $this, 'client_manager' ), 12 );
		}
	}

	/**
	 * Load the license client manager.
	 *
	 * @return  ClientManager Instance of the client manager
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public function client_manager() {
		global $slswc_license_server_url, $slswc_slug, $slswc_text_domain;
		return ClientManager::get_instance( $slswc_license_server_url, $slswc_slug, $slswc_text_domain );
	}

	/**
	 * Adds admin script for plugins
	 *
	 * @return void
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public function client_admin_script() {
		global $slswc_products;

		$screen = get_current_screen();
		if ( 'plugins' !== $screen->id ) {
			return;
		}

		?>
		<script type="text/javascript">
			jQuery( function( $ ){
				$( document ).ready( function() {
					var products = '<?php echo wp_json_encode( $slswc_products ); ?>';
					var $products = $.parseJSON(products);
					if( $( document ).find( '#plugin-information' ) && window.frameElement ) {
						var src = window.frameElement.src;
						<?php
						foreach ( $slswc_products as $slug => $details ) :
							if ( ! is_array( $details ) || array_key_exists( 'slug', $details ) ) {
								continue;
							}
							?>
						if ( undefined != '<?php echo esc_attr( $slug ); ?>' && src.includes( '<?php echo esc_attr( $slug ); ?>' ) ) {
							<?php $url = esc_url_raw( $details['license_server_url'] ) . 'products/' . esc_attr( $slug ) . '/#reviews'; ?>
							<?php // translators: %s - The url to visit. ?>
							$( '#plugin-information' ).find( '.fyi-description' ).html( '<?php echo wp_kses_post( sprintf( __( 'To read all the reviews or write your own visit the <a href="%s">product page</a>.', 'slswcclient' ), $url ) ); ?>');
							$( '#plugin-information' ).find( '.counter-label a' ).each( function() {
								$(this).attr( 'href', '<?php echo esc_attr( $url ); ?>' );
							} );
						}
						<?php endforeach; ?>
					}
				} );
			} );
		</script>
		<?php
	}
}

add_action( 'plugins_loaded', function () {
	global $slswc_updater;
	global $slswc_client;

	$slswc_client = new SLSWC_Client( true );
	$slswc_client->init_hooks();

	$slswc_updater = new Updater( __FILE__, SLSWC_CLIENT_VERSION );
});
