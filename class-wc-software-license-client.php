<?php
/**
 * The WC Software License Client Library 
 *
 * This class defines all code necessary to check for a valid license and available updates stored on your WooCommerce Software License Server  
 * 
 * Documentation can be found here : http://docs.wcvendors.com/wc-software-license-server
 * 
 * To integrate this into your software product include the following code in your MAIN plugin file, do not attempt 
 * to add this code in any other file but your main plugin file. 
 * 
 require_once plugin_dir_path( __FILE__ ) . '../wc-software-license-client/class-wc-software-license-client.php'; 
 *
 *	function wcslc_instance(){ 
 *
 *		 // * @param string  required $license_server_url - The base url to your woocommerce shop 
 *		 // * @param string  required $version - the software version currently running 
 *		 // * @param string  required $text_domain - the text domain of the plugin
 *		 // * @param string  required $plugin_file - path to the plugin file or directory, relative to the plugins directory
 *		 // * @param string  $plugin_nice_name - A nice name for the plugin for use in messages 
 *		 // * @param integer optional $update_interval - time in hours between update checks 
 *		
 *		return WC_Software_License_Client::get_instance( 'http://yourshopurl.here.com', 1.0.0, 'your-text-domain', __FILE__, 'My Cool Plugin' ); 
 *
 *	} // wcslc_instance()
 *
 *	wcslc_instance(); 
 * 
 *
 * @version  	1.0.0 
 * @since      	1.0.0
 * @package    	WC_Software_License_Client
 * @author     	Jamie Madden <support@wcvendors.com>
 * @link       	http://www.wcvendors.com/wc-software-license-server 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Software_License_Client' ) ) :

class WC_Software_License_Client { 

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Version - current plugin version 
	 * @since 1.0.0 
	 */
	public $version; 

	/**
	 * License URL - The base URL for your woocommerce install 
	 * @since 1.0.0 
	 */
	public $license_server_url; 

	/**
	 * Slug - the plugin slug to check for updates with the server 
	 * @since 1.0.0 
	 */
	public $slug; 

	/**
	 * Plugin text domain 
	 * @since 1.0.0 
	 */
	public $text_domain; 

	/**
	 * Path to the plugin file or directory, relative to the plugins directory
	 * @since 1.0.0 
	 */
	public $plugin_file; 

	/**
	 * Path to the plugin file or directory, relative to the plugins directory
	 * @since 1.0.0 
	 */
	public $plugin_nice_name; 

	/**
	 * Update interval - what period in hours to check for updates defaults to 12; 
	 * @since 1.0.0 
	 */
	public $update_interval; 

	/**
	 * Option name - wp option name for license and update information stored as $slug_wc_software_license
	 * @since 1.0.0 
	 */
	public $option_name; 

	/**
	 * The license server host 
	 */
	private $license_server_host; 

	/**
	 * The plugin license key 
	 */
	private $license_key; 

	/**
	 * The domain the plugin is running on 
	 */
	private $domain; 

	/**
	 * Don't allow cloning 
	 *
	 * @since 1.0.0
	 */
	private function __clone() {}

	/**
	 * Don't allow unserializing instances of this class
	 *
	 * @since 1.0.0 
	 */
	private function __wakeup() {}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0 
	 * @param string  $license_server_url - The base url to your woocommerce shop 
	 * @param string  $version - the software version currently running 
	 * @param string  $text_domain - the text domain of the plugin
	 * @param string  $plugin_file - path to the plugin file or directory, relative to the plugins directory
	 * @param integer $update_interval - time in hours between update checks 
	 * @param string  $plugin_nice_name - A nice name for the plugin for use in messages 
	 * @return object A single instance of this class.
	 */
	public static function get_instance( $license_server_url, $version, $text_domain, $plugin_file, $plugin_nice_name, $update_interval = 12 ){

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self( $license_server_url, $version, $text_domain, $plugin_file,  $plugin_nice_name, $update_interval );
		}

		return self::$instance;

	} // get_instance()

	/**
	 * Initialize the class actions 
	 * 
	 * @since 1.0.0 
	 * @param string  $license_server_url - The base url to your woocommerce shop 
	 * @param string  $version - the software version currently running 
	 * @param string  $text_domain - the text domain of the plugin
	 * @param string  $plugin_file - path to the plugin file or directory, relative to the plugins directory
	 * @param string  $plugin_nice_name - A nice name for the plugin for use in messages 
	 * @param integer $update_interval - time in hours between update checks 
	 */
	private function __construct( $license_server_url, $version, $text_domain, $plugin_file, $plugin_nice_name,  $update_interval ){

		
		$this->plugin_nice_name		= $plugin_nice_name; 
		$this->license_server_url 	= $license_server_url; 
		$this->version 				= $version; 
		$this->text_domain			= $text_domain; 
		$this->plugin_file			= plugin_basename( $plugin_file ); 
		$this->update_interval		= $update_interval; 
		$this->debug 				= ( bool ) (constant( 'WP_DEBUG' ) ); 
		$this->slug 				= basename( $this->plugin_file, '.php' ); 	
		$this->option_name 			= $this->slug . '_wc_software_license'; 	
		$this->domain 				= str_ireplace( array( 'http://', 'https://' ), '', home_url() );
		$this->license_details 		= get_option( $this->option_name ); 
		$this->license_manager_url 	= esc_url( admin_url( 'options-general.php?page='. $this->slug . '_license_manager' ) ); 

		// Get the license server host 
		$this->license_server_host 	= @parse_url( $this->license_server_url, PHP_URL_HOST ); 

		add_action( 'admin_init', 								array( $this, 'check_install' ) );
		add_filter( 'pre_set_site_transient_update_plugins',	array( $this, 'update_check') ); 
		add_filter( 'plugins_api', 								array( $this, 'add_plugin_info' ), 10, 3 ); 
		add_filter( 'plugin_row_meta', 							array( $this, 'check_for_update_link' ), 10, 2 ); 
		add_action( 'admin_init', 								array( $this, 'process_manual_update_check' ) ); 
		add_action( 'all_admin_notices',						array( $this, 'output_manual_update_check_result' ) ); 

		// Internal methods 		
		add_filter( 'http_request_host_is_external', 			array( $this, 'fix_update_host' ), 10, 2 ); 

		// Admin Options 
		add_action( 'admin_menu', 								array( $this, 'add_license_menu' ) ); 
		add_action( 'admin_init', 								array( $this, 'load_settings' ) ); 

		// Log the class for debugging purposes 
		if ( $this->debug ) $this->log( $this ); 

	} // __construct()


	/**
	 * Check the installation and configure any defaults that are required 
	 * @since 1.0.0 
	 */
	public function check_install(){ 

		// Set defaults 
		if ( $this->license_details == '' ) { 
			$default_license_options = array( 
				'license_status'		=> 'deactivated', 
				'license_key'			=> '', 
				'current_version'		=> $this->version, 
				'instance_key'			=> '', // Work out a way to generate a unique key to help with security for updates 
			); 

			update_option( $this->option_name, $default_license_options ); 

		}

		if ( $this->license_details == '' || $this->license_details[ 'license_status' ] == 'deactivated' ){ 
			add_action( 'admin_notices', array( $this, 'license_inactive' ) );  
		}

	} // check_install() 


	/**
	 * Display a license inactive notice 
	 */
	public function license_inactive(){ 

		if ( ! current_user_can( 'manage_options' ) ) return; 
		
		echo '<div class="error notice is-dismissible"><p>'. 
			sprintf( __( 'The %s license key has not been activated, so you will be unable to get automatic updates or support! %sClick here%s to activate your support and updates license key.', 'wcvendors-pro' ), $this->plugin_nice_name, '<a href="' . $this->license_manager_url . '">', '</a>' ) . 
		'</p></div>'; 

	} // license_inactive() 

	/**
	 * Check for updates with the license server 
	 * @since 1.0.0 
	 * @param object transient object from the update api 
	 * @return object transient object possibly modified 
	 */
	public function update_check( $transient ){ 

		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$request_args = array( 
			'action' 			=> 'update_check', 
		); 

		$plugin_update_info = $this->get_plugin_info( $request_args ); 

		if ( isset( $plugin_update_info ) && is_object( $plugin_update_info ) ) { 

			$new_version = (string) $plugin_update_info->new_version; 

			if ( isset( $new_version ) ){ 
				if ( version_compare( $new_version, $this->version, '>' ) ){ 
					$transient->response[ $this->plugin_file ] = $plugin_update_info; 
				}

			}

		}

		return $transient; 

	} // update_check() 

	/**
	 * Add the plugin information to the WordPress Update API 
	 *
	 * @since 1.0.0 
	 * @param bool|object The result object. Default false.
	 * @param string The type of information being requested from the Plugin Install API.
	 * @param object Plugin API arguments. 
	 * @return object  
	 */
	public function add_plugin_info( $result, $action, $args ){ 

		$version = get_site_transient( 'update_plugins' ); 

		// Is the current API call about plugin information ? 
		// if ( $action == 'plugin_information' ) { 

		// 	// Is this about our plugin? 
		if ( isset( $args->slug ) && $args->slug != $this->slug ){ 
			return false; 				
		} 

		$request_args = array( 
			'action'  => 'info', 
		);

		$plugin_info = $this->get_plugin_info( $request_args ); 

		if ( isset( $plugin_info ) && is_object( $plugin_info ) && $plugin_info !== false ){ 

			return $plugin_info; 
		}
		
	} // add_plugin_info() 


	/**
	 *  Communicates with the license server 
	 */
	public function get_plugin_info( $request_args = array() ){ 

		$request_args[ 'current_version' ] 		= $this->version; 
		$request_args[ 'slug' ] 				= $this->slug; 
		$request_args[ 'license_key' ] 			= $this->license_key; 

		// Allow filtering the request args for plugins 
		$request_args = apply_filters( 'wcsl_request_args_' . $this->slug, $request_args ); 

		if ( $this->debug ) $this->log( $request_args ); 

		// Query the license server 
		$response = wp_remote_post( $this->license_server_url, array( 'body' => $request_args ) ); 

		// Validate that the response is valid 
		$result = $this->validate_response( $response ); 

		$this->log( $result ); 

		// Check if there is an error and display it if there is one, otherwise process the response. 
		if ( is_wp_error( $result ) ){ 
			echo $result->get_error_message(); 
		} else { 

			// actually process the response once the server is built 

		 	$plugin_update_info = new stdClass; 
			$plugin_update_info->id = ''; 
			$plugin_update_info->slug 			= $this->slug; 
			$plugin_update_info->new_version 	= '1.3.4'; 
			$plugin_update_info->plugin 		= $this->plugin_file;
			$plugin_update_info->package 		= 'https://someupdateurl.com/nextversion.zip'; 
			$plugin_update_info->url 			= 'https://someupdateurl.com/myproduct'; 
			$plugin_update_info->tested			= '4.5.3'; 

			return $plugin_update_info; 

		}


	} // check_for_update() 


	/**
	 * Validate the license server response 
	 * @since 1.0.0 
	 * @param WP_Error | Array The response or WP_Error 
	 */
	public function validate_response( $response ){ 

		if ( !empty ( $response ) ) { 

			if ( is_wp_error( $response ) ){ 
				return new WP_Error( $response->get_error_code(), sprintf( __( 'HTTP Error: %s', $this->text_domain ), $response->get_error_message() ) ); 
			}

			if ( !isset( $response[ 'response'][ 'code'] ) ){ 
				return new WP_Error( 'wc_software_license_no_response_code', __( 'wp_remote_get() returned an unexpected result.', $this->text_domain ) ); 
			}

			if ( $response[ 'response'][ 'code'] !== 200 ){ 
				return new WP_Error( 'wc_software_license_unexpected_response_code', sprintf( __( 'HTTP response code is : % s, expecting ( 200 )', $this->text_domain ), $response[ 'result'][ 'code'] ) ); 
			}

			if ( empty( $response[ 'body' ] ) ){ 
				return new WP_Error( 'wc_software_license_no_response', __( 'The server returned no response', $this->text_domain ) ); 
			}

			return true; 

		} 

	} // validate_response() 


	/**
	 * Add a check for update link on the plugins page. You can change the link with the supplied filter. 
	 * returning an empty string will disable this link   
	 *
	 * @since 1.0.0 
	 * @param array  $links The array having default links for the plugin.
	 * @param string $file The name of the plugin file.
	 */
	public function check_for_update_link( $links, $file ){ 

		// Only modify the plugin meta for our plugin 
		if ( $file == $this->plugin_file && current_user_can( 'update_plugins' ) ){ 

			$update_link_url = wp_nonce_url( 
				add_query_arg( array( 
						'wcsl_check_for_update' => 1, 
						'wcsl_slug' => $this->slug 
					), 
					self_admin_url( 'plugins.php' ) 
				), 
				'wcsl_check_for_update'
			); 

			$update_link_text = apply_filters( 'wcsl_update_link_text_'. $this->slug, __( 'Check for updates', $this->text_domain ) ); 

			if ( !empty ( $update_link_text ) ){ 
				$links[] = sprintf( '<a href="%s">%s</a>', esc_attr( $update_link_url ), $update_link_text ); 
			}

		}

		return $links; 

	} // check_for_update_link() 

	/**
	 * Process the manual check for update if check for update is clicked on the plugins page. 
	 * 
	 * @since 1.0.0 
	 */
	public function process_manual_update_check(){ 

		if ( isset( $_GET[ 'wcsl_check_for_update' ], $_GET[ 'wcsl_slug' ]) && $_GET[ 'wcsl_slug' ] == $this->slug && current_user_can( 'update_plugins') && check_admin_referer( 'wcsl_check_for_update' ) ){ 

			// Check for updates / hardcoded for now 
			$update_available = true; 

			$status = ( $update_available == null ) ? 'no' : 'yes'; 

			wp_redirect( add_query_arg( 
					array( 
						'wcsl_update_check_result' => $status, 
						'wcsl_slug'	=> $this->slug, 
					), 
					self_admin_url('plugins.php')
				)
			); 
		}

	} // process_manual_update_check() 


	/**
	 * Out the results of the manual check 
	 * @since 1.0.0 
	 */
	public function output_manual_update_check_result(){ 

		if ( isset( $_GET[ 'wcsl_update_check_result'], $_GET[ 'wcsl_slug' ] ) && ( $_GET[ 'wcsl_slug'] == $this->slug ) ){ 

			$check_result = $_GET[ 'wcsl_update_check_result' ]; 

			switch ( $check_result ) {
				case 'no':
					$admin_notice = __( 'This plugin is up to date. ', $this->text_domain ); 
					break; 
				case 'yes': 
					$admin_notice = __( 'An update is available for this plugin.', $this->text_domain ); 
					break;
				default:
					$admin_notice = __( 'Unknown update status.', $this->text_domain ); 
					break;
			}

			printf( '<div class="updated notice is-dismissible"><p>%s</p></div>', apply_filters( 'wcsl_manual_check_message_result_' . $this->slug, $admin_notice, $check_result ) ); 
		}

	} // output_manual_update_check_result() 


	/**
	 * This is for internal purposes to ensure that during development the HTTP requests go through 
	 * due to security features in the WordPress HTTP API. 
	 * 
	 * Source for this solution: Plugin Update Checker Library 3.1 by Janis Elsts 
	 *
	 * @param bool $allow
	 * @param string $host
	 * @return bool
	 */
	private function fix_update_host( $allow, $host ){ 

		if ( strtolower( $host) === strtolower( $this->license_server_url ) ){ 
			return true; 
		}
		return $allow; 

	} //fix_update_host() 


	/**
	 * Class logger so that we can keep our debug and logging information cleaner 
	 * @since 1.0.0 
	 * @param mixed - the data to go to the error log 
	 */
	private function log( $data ){ 

		if ( is_array( $data ) || is_object( $data ) ) { 
			error_log( print_r( $data, true ) ); 
		} else { 
			error_log( $data );
		}

	} // log() 


	/**
	 * Add the admin menu to the dashboard 
	 * @since 1.0.0 
	 */
	public function add_license_menu(){ 

		$this->log( $this ); 

		$page = add_options_page( 
			sprintf( __( '%s License', $this->text_domain ), $this->plugin_nice_name ),
			sprintf( __( '%s License', $this->text_domain ), $this->plugin_nice_name ),
			'manage_options', 
			$this->slug . '_license_manager', 
			array( $this, 'license_page' )
		); 

	} // add_license_menu()

	/**
	 * License page output call back function 
	 */
	public function license_page(){ 
	?>
		<div class='wrap'>
			<?php screen_icon(); ?>
			<h2><?php printf( __( '%s License Manager', $this->text_domain ), $this->plugin_nice_name ) ?></h2>
			<form action='options.php' method='post'>
				<div class="main">
					<div class="notice update">
							<?php printf( __( 'Please Note: If your license is active on another website you will need to deactivate this in your my account page before being able to activate it on this site. If you have problems activating the license key try deactivating and then activating %s in your plugins menu again.', $this->text_domain), $this->plugin_nice_name ); ?>
					</div>

					<?php 
						settings_fields(  $this->slug . '_license_information' ); 
						do_settings_sections( $this->slug .'_license_activation' );
						submit_button( __( 'Save Changes', $this->text_domain ) );
					?>
				</div>
			</form>
		</div>

	<?
	} // license_page() 

	/**
	 * Load settings for the admin screens so users can input their license key 
	 *
	 * Utilizes the WordPress Settings API to implment this
	 */
	public function load_settings(){ 

		register_setting( $this->slug . '_license_information', $this->slug . '_license_information', array( $this, 'validate_license_options' ) ); 

		add_settings_section( 
			$this->slug .'_license_activation', 
			sprintf( __( '%s License Activation', $this->text_domain ), $this->plugin_nice_name ), 
			array( $this, 'license_activation_field' ), 
			$this->slug . '_license_manager'
		 ); 

		// License Key 
		add_settings_field(
			$this->slug .'_license_key',  
			sprintf( __( '%s License key', $this->text_domain ), $this->plugin_nice_name ), 
			array( $this, 'license_key_field' ), 
			$this->slug . '_license_manager', 
			$this->slug .'_license_activation',
			array( 'label_for' => $this->slug .'_license_key' )
		); 

	} // load_settings() 

	/**
	 * Validate the license key information sent from the form. 
	 * 
	 */
	public function validate_license_options( $input ){ 

	} // validate_license_options() 

	/**
	 * License key field callback
	 */
	public function license_key_field( ){ 

		echo '<input type="text" value="' . $this->license_details[ 'license_key' ] . '" />'; 

	} // license_key_field() 


} // WC_Software_License_Client 

endif; 