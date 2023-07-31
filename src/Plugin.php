<?php
/**
 * Defines the plugin updater class
 *
 * @version     1.0.2
 * @since       1.0.2
 * @package     Client
 * @link        https://licenseserver.io/
 */

namespace Madvault\Slswc\Client;

use Madvault\Slswc\Client\ApiClient;

class Plugin {
	/**
	 * The instance of this class.
	 *
	 * @var plugin
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public static $instance = null;

	/**
	 * The plugin version
	 *
	 * @var string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $version;

	/**
	 * The plugin file
	 *
	 * @var string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $plugin_file;

	/**
	 * Software slug
	 *
	 * @var string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $slug;

	/**
	 * The dir and file of the plugin
	 *
	 * @var string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $plugin_dir_file;

	/**
	 * The instance of the ApiClient class.
	 *
	 * @var ApiClient
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	private $client;

	/**
	 * The license details class.
	 *
	 * @var LicenseDetails
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	private $license;

	/**
	 * Get an instance of this class..
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @param   string $license_server_url - The base url to your WooCommerce shop.
	 * @param   string $base_file          - path to the plugin file or directory, relative to the plugins directory.
	 * @param   array  $args               - array of additional arguments to override default ones.
	 */
	public static function get_instance( $license_server_url, $base_file, $args ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $license_server_url, $base_file, $args );
		}

		return self::$instance;
	}

	/**
	 * Initialize the class.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @param   string $plugin_file          - path to the plugin file or directory, relative to the plugins directory.
	 * @param   array  $args               - array of additional arguments to override default ones.
	 *
	 * @return void
	 * 
	 * @example
	 *   $plugin = new Plugin( 'https://licenseserver.io', __FILE__, array(
	 * 		 'slug'        => 'my-plugin',
	 * 		 'version'     => '1.0.0',
	 * 		 'license_key' => 'LICENSE_KEY',
	 *   ) );
	 */
	public function __construct( $license_server_url, $plugin_file, $args = array() ) {
		
		$this->plugin_file  = $plugin_file;

		$args = Helper::get_file_details( $this->plugin_file, $args );

		$this->slug = $args['slug'];
		$this->version = $args['version'];

		$this->plugin_dir_file = $this->plugin_dir_file();

		$this->client  = ApiClient::get_instance( $license_server_url, $this->slug );
		$this->license = new LicenseDetails( $plugin_file, $args );
	}

	/**
	 * Initialize Hooks
	 *
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function init_hooks() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_check' ) );
		add_filter( 'plugins_api', array( $this, 'add_plugin_info' ), 10, 3 );
		add_filter( 'plugin_row_meta', array( $this, 'check_for_update_link' ), 10, 2 );
		add_filter( 'extra_plugin_headers', array( $this, 'extra_headers' ) );
		add_filter( 'site_transient_update_plugins', array( $this, 'change_update_information' ) );
		add_action( 'in_plugin_update_message-' . $this->plugin_dir_file, array( $this, 'need_license_message' ), 10, 2 );
	}

	/**
	 * Get the plugin folder and base name based on the file path
	 *
	 * @return string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function plugin_dir_file() {
		$plugin_folder = '';
		$plugin_file = '';

		// Normalize the file path by removing any trailing slashes
		$file_path = rtrim($this->plugin_file, '/');

		// Extract the folder name and file name from the file path
		$file_parts = explode('/', $file_path);
		$file_count = count($file_parts);

		if ($file_count >= 2) {
			$plugin_folder = $file_parts[$file_count - 2];
			$plugin_file = $file_parts[$file_count - 1];
		}

		// Return the plugin folder and file name as a string
		return $plugin_folder . '/' . $plugin_file;
	}

	/**
	 * Check for updates with the license server.
	 *
	 * @since  1.0.0
	 * @param  object $transient object from the update api.
	 * @return object $transient object possibly modified.
	 */
	public function update_check( $transient ) {
		Helper::log('Update check: ' .  print_r( $this->license->get_license_details(), true ));

		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$response = $this->client->request(
			'check_update',
			$this->license->get_license_details()
		);

		if ( ! $this->license->check_license( $response ) ) {
			Helper::log('License check failed');
			return $transient;
		}

		Helper::log('The license is valid. Check software details' );

		if ( isset( $response ) && is_object( $response->software_details ) ) {
			$plugin_update_info = $response->software_details;

			if ( ! isset( $plugin_update_info->new_version ) ) {
				return $transient;
			}

			if ( version_compare( $plugin_update_info->new_version, $this->get_version(), '>' ) ) {
				// Required to cast as array due to how object is returned from api.
				$plugin_update_info->sections              = (array) $plugin_update_info->sections;
				$plugin_update_info->banners               = (array) $plugin_update_info->banners;
				$transient->response[ $this->plugin_file ] = $plugin_update_info;
			}
		}

		return $transient;
	}

	/**
	 * Add the plugin information to the WordPress Update API.
	 *
	 * @since  1.0.0
	 * @param  bool|object $result The result object. Default false.
	 * @param  string      $action The type of information being requested from the Plugin Install API.
	 * @param  object      $args Plugin API arguments.
	 * @return object
	 */
	public function add_plugin_info( $result, $action = null, $args = null ) {

		// Is this about our plugin?
		if ( isset( $args->slug ) ) {

			if ( $args->slug !== $this->slug ) {
				return $result;
			}
		} else {
			return $result;
		}

		$server_response    = $this->client->request();
		$plugin_update_info = $server_response->software_details;

		// Required to cast as array due to how object is returned from api.
		$plugin_update_info->sections = (array) $plugin_update_info->sections;
		$plugin_update_info->banners  = (array) $plugin_update_info->banners;
		$plugin_update_info->ratings  = (array) $plugin_update_info->ratings;
		if ( isset( $plugin_update_info ) && is_object( $plugin_update_info ) && false !== $plugin_update_info ) {
			return $plugin_update_info;
		}

		return $result;
	}

	/**
	 * Add a check for update link on the plugins page. You can change the link with the supplied filter.
	 * returning an empty string will disable this link
	 *
	 * @since 1.0.0
	 * @param array  $links The array having default links for the plugin.
	 * @param string $file The name of the plugin file.
	 */
	public function check_for_update_link( $links, $file ) {
		// Only modify the plugin meta for our plugin.
		if ( stripos( $this->plugin_file, $file ) && current_user_can( 'update_plugins' ) ) {

			$update_link_url = wp_nonce_url(
				add_query_arg(
					array(
						'slswc_check_for_update' => 1,
						'slswc_slug'             => $this->slug,
					),
					self_admin_url( 'plugins.php' )
				),
				'slswc_check_for_update'
			);

			$update_link_text = apply_filters(
				'slswc_update_link_text_' . $this->slug,
				__( 'Check for updates', 'slswcclient' )
			);

			if ( ! empty( $update_link_text ) ) {
				$links[] = sprintf( '<a href="%s">%s</a>', esc_attr( $update_link_url ), $update_link_text );
			}
		}

		return $links;
	}

	/**
	 * Change update information
	 *
	 * @param object $transient The transient object.
	 * @return object $transient The possibly modified transient object.
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function change_update_information ( $transient ) {
		//If we are on the update core page, change the update message for unlicensed products
		global $pagenow;
		$update_core = ( 'update-core.php' == $pagenow ) ? true : false;
		
		if ( $update_core && $transient && isset( $transient->response ) && ! isset( $_GET['action'] ) ) {

			Helper::log('Change plugin update information. Current transient: ' . print_r( $transient, true ) );

			$notice_text = __(
				'To enable this update please activate your license in Settings > License Manager page.',
				'slswcclient'
			);

			if ( ! isset( $transient->response[ $this->plugin_dir_file ] ) ) {
				Helper::log('No update for ' . $this->plugin_dir_file );
				return $transient;
			}

			$plugin_response = $transient->response[ $this->plugin_file ];

			$plugin_has_update = isset( $plugin_response ) && isset( $plugin_response->package ) ? true: false;

		  Helper::log('Plugin response: ' . print_r( $plugin_response, true ) );

			// $upgrade_notice = ( FALSE === stristr( $plugin_response->upgrade_notice, $notice_text ) );

			$has_upgrade_notice = isset( $plugin_response->upgrade_notice ) && ! empty( $plugin_response->upgrade_notice );

			if( $plugin_has_update && '' == $plugin_response->package && $has_upgrade_notice ) {
				Helper::log('Update package: ' . $plugin_response->package . ', upgrade notice: ' . $notice_text );
				$message = '<div class="slswcclient-plugin-upgrade-notice">' . $notice_text . '</div>';
				$plugin_response->upgrade_notice = wp_kses_post( $message );
			}
		}

		return $transient;
	}

	/**
	 * Add action for queued products to display message for unlicensed products.
	 *
	 * @param array $plugin_data
	 * @param object $update
	 * @return void
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public function need_license_message ( $plugin_data, $update ) {
		error_log('In plugin update needs license');
		if ( ! empty( $update->package ) ) {
			return;
		}

		echo wp_kses_post(
			sprintf(
				'<div class="slswcclient-plugin-upgrade-notice">%s</div>',
			 __( 'To enable this update please activate your license', 'slswcclient' )
			)
		);
	}

	/**
	 * Extra plugin headers.
	 *
	 * @param array $headers The array of headers.
	 * @return array
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public function extra_headers( $headers ) {
		return Helper::extra_headers( $headers );
	}

	/**
	 * Getters
	 *
	 * Define getters to get the plugin version and slug.
	 */

	/**
	 * Get the plugin version.
	 *
	 * @return string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get the slug.
	 *
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Getters & Setters
	 *
	 * Define getters to get the plugin version and slug.
	 */

	 /**
		* Set the plugin slug.
		*
		* @param string $slug The plugin slug.
		* @return void
		* @version 1.0.0
		* @since   1.0.0
		*/
	public function set_slug( $slug ) {
		$this->slug = $slug;
	}

	/**
	 * Set the plugin version
	 *
	 * @param string $version The plugin version.
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function set_version( $version ) {
		$this->version = $version;
	}
}