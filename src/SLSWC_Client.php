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
 *      // Required Parameters.
 *
 *      @param string  required $license_server_url - The url to the license server.
 *      @param string  required $plugin_file - The path to the main plugin file.
 *
 *      // Optional Parameters.
 *      @param string  optional $product_type - The type of product. plugin/theme
 *
 *  require_once plugin_dir_path( __FILE__ ) . 'path/to/class-slswc-client.php';
 *
 *  function slswc_instance(){
 *      return SLSWC_Client::get_instance( 'http://yourshopurl.here.com', $plugin_file, $product_type );
 *  } // slswc_instance()
 *
 *  slswc_instance();
 *
 * @version     1.0.0
 * @since       1.0.0
 * @package     SLSWC_Client
 * @link        https://licenseserver.io/
 */

namespace MadVault\SLSWC\Client;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class responsible for a single product.
 *
 * @version 1.0.0
 * @since   1.0.0
 */
class SLSWC_Client {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * License Client
	 *
	 * @var Client
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	protected $client = null;

	/**
	 * Return an instance of this class.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @param   string $license_server_url - The base url to your WooCommerce shop.
	 * @param   string $base_file - path to the plugin file or directory, relative to the plugins directory.
	 * @param   string $software_type - the type of software this is. plugin|theme, default: plugin.
	 * @param   mixed  ...$args - array of additional arguments to override default ones.
	 * @return  object A single instance of this class.
	 */
	public static function get_instance( $license_server_url, $base_file, $software_type = 'plugin', ...$args ) {
		if ( ! is_null( self::$instance ) ) {
			self::$instance = new self( $license_server_url, $base_file, $software_type, $args );
		}

		return self::$instance;
	}

	/**
	 * Initialize the class actions.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @param   string $license_server_url - The base url to your WooCommerce shop.
	 * @param   string $base_file - path to the plugin file or directory, relative to the plugins directory.
	 * @param   string $software_type - the type of software this is. plugin|theme, default: plugin.
	 * @param   array  $args - array of additional arguments to override default ones.
	 */
	public function __construct( $license_server_url, $base_file, $software_type, $args ) {
		$this->client = LicenseClient::get_instance( $license_server_url, $base_file, $software_type, $args );

		return $this;
	}

	/**
	 * Load the license manager class once all plugins are loaded.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function init_client_hooks() {
		add_filter( 'extra_plugin_headers', 'slswc_extra_headers' );
		add_filter( 'extra_theme_headers', 'slswc_extra_headers' );

		return $this;
	}

	/**
	 * Load the license manager class once all plugins are loaded.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function init_client_manager() {
		add_action( 'admin_init', array( $this, 'client_manager' ), 12 );
		add_action( 'after_setup_theme', array( $this, 'client_manager' ) );
		add_action( 'admin_footer', array( $this, 'client_admin_script' ), 11 );

		return $this;
	}

	/**
	 * Print admin script for SLSWC Client.
	 *
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function client_admin_script() {

		global $slswc_license_server_url, $slswc_slug, $slswc_text_domain, $slswc_products;

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

	/**
	 * Load the license client manager.
	 *
	 * @return  Manager Instance of the client manager
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function client_manager() {
		global $slswc_license_server_url, $slswc_slug, $slswc_text_domain;
		return ClientManager::get_instance( $slswc_license_server_url, $slswc_slug, $slswc_text_domain );
	}
}

// @todo: remove, this is a sample integration with the optional client manager and hooks;
$client = SLSWC_Client::get_instance( $license_server_url, $base_file, $software_type, $args );
$client->init_client_hooks(); // @todo: These hooks are required, move to load internally on the client
$client->init_client_manager(); // @todo: This is the optional client manager.
