<?php
/**
 * Defines the license manager class for SLSWC
 *
 * @version     1.0.2
 * @since       1.0.2
 * @package     LicenseManager
 * @link        https://licenseserver.io/
 */

namespace Madvault\Slswc\Client;

use Madvault\Slswc\Client\ClientManager;
use Madvault\Slswc\Client\Helper;

/**
 * Class responsible for a single product.
 *
 * @version 1.0.0
 * @since   1.0.0
 */
//phpcs:ignore
class LicenseManager {
	/**
	 * Instance of this class.
	 *
	 * @var LicenseManager
	 */
	private static $instance = null;

	/**
	 * Version - current plugin version
	 *
	 * @var string $version
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $version;

	/**
	 * License URL - The base URL for your WooCommerce install
	 *
	 * @var string $license_server_url
	 * @version 1.0.0
	 * @since 1.0.0
	 */
	public $license_server_url;

	/**
	 * Slug - the plugin slug to check for updates with the server
	 *
	 * @var string $slug
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $slug;

	/**
	 * Plugin text domain
	 *
	 * @var string $text_domain
	 * @version 1.0.0
	 * @since 1.0.0
	 */
	public $text_domain;

	/**
	 * Path to the plugin file or directory, relative to the plugins directory
	 *
	 * @var string $base_file
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $base_file;

	/**
	 * Path to the plugin file or directory, relative to the plugins directory
	 *
	 * @var string $name
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $name;

	/**
	 * Update interval - what period in hours to check for updates defaults to 12;
	 *
	 * @var string $update_interval
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $update_interval;

	/**
	 * Option name - wp option name for license and update information stored as $slug_wc_software_license.
	 *
	 * @var string $option_name
	 * @version 1.0.0
	 * @since 1.0.0
	 */
	public $option_name;

	/**
	 * The license server host.
	 *
	 * @var string $version
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	private $license_server_host;

	/**
	 * The plugin license key.
	 *
	 * @var string $version
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	private $license_key;

	/**
	 * The domain the plugin is running on.
	 *
	 * @var string $version
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	private $domain;

	/**
	 * The plugin license key.
	 *
	 * @var string $version
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	private $admin_notice;

	/**
	 * The current environment on which the client is install.
	 *
	 * @var     string
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	private $environment;

	/**
	 * The plugin file
	 *
	 * @var string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $plugin_file;

	/**
	 * Whether to enable debugging or not.
	 *
	 * @var bool
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $debug;
	
	/**
	 * License details
	 *
	 * @var array
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $license_details;

	/**
	 * The theme file
	 *
	 * @var string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $theme_file;

	/**
	 * The license server url.
	 *
	 * @var string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $license_manager_url;

	/**
	 * The software type.
	 *
	 * @var string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $software_type;

	/**
	 * Additional arguments to override default ones.
	 *
	 * @var array
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $args = array();

	/**
	 * List of messages
	 *
	 * @var array
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $messages = array();

	/**
	 * Instance of ApiClient
	 *
	 * @var ApiClient
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	private $client;

	/**
	 * License details
	 *
	 * @var LicenseDetails
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $license;

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
	public static function get_instance( $license_server_url, $base_file, $software_type = 'plugin', $args = array() ) {
		if ( null === self::$instance ) {
			self::$instance = new self(
				$license_server_url,
				$base_file,
				$software_type,
				$args
			);
		}
		return self::$instance;

	} // get_instance

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
	private function __construct( $license_server_url, $base_file, $software_type = 'plugin', $args = array() ) {
		$args = Helper::get_file_details( $base_file, $args );

		$this->args = $args;

		if ( 'plugin' === $software_type ) {
			$this->plugin_file = plugin_basename( $base_file );
			$this->slug        = empty( $args['slug'] ) ? basename( $this->plugin_file, '.php' ) : $args['slug'];
		} else {
			$this->theme_file = $base_file;
			$this->slug       = empty( $args['slug'] ) ? basename( $this->theme_file, '.css' ) : $args['slug'];
		}

		$this->client = ApiClient::get_instance( $license_server_url, $this->slug );
		$this->license = new LicenseDetails( $this->base_file, $this->license_details );

		$this->base_file   = $base_file;
		$this->name        = empty( $args['name'] ) && ! empty( $args['title'] ) ? $args['title'] : $args['name'];
		$this->version     = empty( $args['version'] ) ? '1.0.0' : $args['version'];
		$this->text_domain = empty( $args['text_domain'] ) ? $this->slug : $args['text_domain'];

		$this->license_server_url = apply_filters(
			'slswc_license_server_url_for_' . $this->slug,
			trailingslashit( $license_server_url )
		);

		$this->update_interval = empty( $args['update_interval'] ) ? 12 : $args['update_interval'];
		$this->debug           = apply_filters(
			'slswc_client_logging',
			defined( 'WP_DEBUG' ) && WP_DEBUG ? true : $args['debug']
		);

		$this->option_name   = $this->slug . '_license_details';
		$this->domain        = untrailingslashit( str_ireplace( array( 'http://', 'https://' ), '', home_url() ) );
		$this->software_type = $software_type;
		$this->environment   = isset( $args['environment'] ) ? $args['environment'] : '';

		$default_license_options = $this->license->get_default_license_details();
		$this->license_details   = get_option( $this->option_name, $default_license_options );

		$this->license_manager_url = esc_url( admin_url( 'options-general.php?page=slswc_license_manager&tab=licenses' ) );

		// Get the license server host.
		// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
		$license_server_host = @wp_parse_url( $this->license_server_url, PHP_URL_HOST );
		//phpcs:enable
		// phpcs:ignore
		$this->license_server_host = apply_filters( 'slswc_license_server_host_for_' . $this->slug, $license_server_host);

		Helper::log( "License Server Url: $this->license_server_url" );
		Helper::log( "Base file: $base_file" );
		Helper::log( "Software type: $software_type" );
		Helper::log( $args );
	}

	/**
	 * Get the default args
	 *
	 * @return  array $args
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public static function get_default_args() {
		return array(
			'update_interval' => 12,
			'debug'           => false,
			'environment'     => '',
		);
	}

	/**
	 * Initialize action hooks and filters
	 *
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function init_hooks() {
		if ( ! is_admin() ) {
			return;
		}
		// Don't run the license activation code if running on local host.
		$host = isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : '';
		if ( Helper::is_dev( $host, $this->environment ) && ( ! empty( $this->args['debug'] ) && ! $this->args['debug'] ) ) {

			add_action( 'admin_notices', array( $this, 'license_localhost' ) );
			return;

		}
		
		// Initialize wp-admin interfaces.
		add_action( 'admin_init', array( $this, 'check_install' ) );

		// Internal methods.
		add_filter( 'http_request_host_is_external', array( $this, 'fix_update_host' ), 10, 2 );

		add_action( 'wp_ajax_slswc_activate_license', array( $this, 'activate_license' ) );

		// Validate license on save.
		add_action( 'slswc_save_license_' . $this->slug, array( $this, 'validate_license' ), 99 );

		/**
		 * Only allow updates if they have a valid license key.
		 * Or API keys are set to check for updates.
		 */

		//TODO:Remove this test data
		$this->license_details['license_status'] = 'active';
		$this->license_details['active_status']['live'] = 'yes';
		// End todo

		$allowed_statuses = array( 'active', 'expiring' );
		$license_status   = $this->license_details['license_status'];

		if ( ! in_array( $license_status, $allowed_statuses ) && ! Helper::is_connected() ) {
			return;
		}

		error_log(' License Data: ' . print_r( $this->license_details, true ) );

		add_action( 'admin_init', array( $this, 'process_manual_update_check' ) );
		add_action( 'all_admin_notices', array( $this, 'output_manual_update_check_result' ) );
	}

	

	/**
	 * Check the installation and configure any defaults that are required
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @todo move this to a plugin activation hook
	 */
	public function check_install() {

		// Set defaults.
		if ( empty( $this->license_details ) ) {
			$this->license_details = $this->license->get_default_license_details();
			update_option( $this->option_name, $this->license_details );
		}

		$license_status    = $this->license_details['license_status'];
		$inactive_statuses = array( 'inactive', 'deactivated' );
		$active_statuses   = array( 'active', 'expiring', 'expired' );

		if ( in_array( $license_status, $inactive_statuses, true ) ) {
			add_action( 'admin_notices', array( $this, 'license_inactive' ) );
		}

		if ( in_array( $license_status, $active_statuses, true ) ) {
			add_action( 'admin_notices', array( $this, 'license_inactive' ) );
		}
	}

	/**
	 * Display a license inactive notice
	 */
	public function license_inactive() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="error notice is-dismissible"><p>';
		// phpcs:disable
		// translators: 1 - Product name. 2 - Link opening html. 3 - link closing html.
		echo sprintf(
			__(
				'The %1$s license key has not been activated, so you will not be able to get automatic updates or support! %2$sClick here%3$s to activate your support and updates license key.', 
				'slswcclient'
			),
			esc_attr( $this->name ),
			'<a href="' . esc_url_raw( $this->license_manager_url ) . '">',
			'</a>' 
		);
		echo '</p></div>';
		// phpcs:enable
	}

	/**
	 * Display the localhost detection notice
	 */
	public function license_localhost() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="error notice is-dismissible"><p>';
		// translators: 1 - Product name.
		echo esc_attr(
			sprintf(
				__(
					'%s has detected you are running on your localhost. The license activation system has been disabled.',
					'slswcclient'
				),
				esc_attr( $this->name )
			)
		) . '</p></div>';
	}

	/**
	 * Process the manual check for update if check for update is clicked on the plugins page.
	 *
	 * @since 1.0.0
	 */
	public function process_manual_update_check() {
		if ( current_user_can( 'update_plugins' ) || check_admin_referer( 'slswc_check_for_update' ) ) {
			return;
		}

		$is_update_check = isset( $_GET['slswc_check_for_update'] ) && isset( $_GET['slswc_slug'] );
		// phpcs:ignore
		if ( ! $is_update_check || $_GET['slswc_slug'] !== $this->slug  ) {
			return;
		}

		// Check for updates.
		$response = $this->client->request();

		if ( ! $this->license->check_license( $response ) ) {

			if ( isset( $server_response ) && is_object( $server_response->software_details ) ) {

				$plugin_update_info = $server_response->software_details;

				if ( isset( $plugin_update_info ) && is_object( $plugin_update_info ) ) {

					if ( version_compare( (string) $plugin_update_info->new_version, (string) $this->version, '>' ) ) {

						$update_available = true;

					} else {

						$update_available = false;
					}
				} else {

					$update_available = false;
				}

				$status = ( null === $update_available ) ? 'no' : 'yes';

				wp_safe_redirect(
					add_query_arg(
						array(
							'slswc_update_check_result' => $status,
							'slswc_slug' => $this->slug,
						),
						self_admin_url( 'plugins.php' )
					)
				);
			}
		}

	} // process_manual_update_check


	/**
	 * Out the results of the manual check
	 *
	 * @since 1.0.0
	 */
	public function output_manual_update_check_result() {

		// phpcs:ignore
		$is_update_check = isset( $_GET['slswc_update_check_result'] ) && isset( $_GET['slswc_slug'] );

		if ( $is_update_check && ( $_GET['slswc_slug'] === $this->slug ) ) {

			// phpcs:ignore
			$check_result = wp_unslash( $_GET['slswc_update_check_result'] );

			switch ( $check_result ) {
				case 'no':
					$admin_notice = __( 'This plugin is up to date. ', 'slswcclient' );
					break;
				case 'yes':
					// translators: 1 - Plugin/Theme name.
					$admin_notice = sprintf( __( 'An update is available for %s.', 'slswcclient' ), $this->name );
					break;
				default:
					$admin_notice = __( 'Unknown update status.', 'slswcclient' );
					break;
			}

			printf(
				'<div class="updated notice is-dismissible"><p>%s</p></div>',
				esc_attr(
					apply_filters(
						'slswc_manual_check_message_result_' . $this->slug,
						$admin_notice,
						$check_result
					)
				)
			);
		}

	} // output_manual_update_check_result

	/**
	 * This is for internal purposes to ensure that during development the HTTP requests go through.
	 * This is due to security features in the WordPress HTTP API.
	 *
	 * Source for this solution: Plugin Update Checker Library 3387.1 by Janis Elsts.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param bool   $allow Whether to allow or not.
	 * @param string $host  The host name.
	 * @return bool
	 */
	public function fix_update_host( $allow, $host ) {

		if ( strtolower( $host ) === strtolower( $this->license_server_host ) ) {
			return true;
		}
		return $allow;
	}

	/**
	 * Activate a license
	 *
	 * @return void
	 * @version 1.0.2
	 * @since   1.0.2
	 */
	public function activate_license() {
		$request_args = array (
			'slug'        => $this->slug,
			'license_key' => $this->license_key,
			'domain'      => $this->domain,
			'version'     => $this->version,
			'environment' => $this->environment,
		);

		$empty_args = array();
		$has_empty  = false;

		foreach ( $request_args as $key => $value ) {
			if ( $value == '' && $key !== 'environment' ) {
				$has_empty = true;
				$empty_args[] = $key;
			}
		}

		if ( ! empty( $empty_args ) && $has_empty ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						__( 'Missing required parameter. The following args are required but not included in your request: %s', 'slswclient' ),
						implode( ',', $empty_args )
					),
				)
			);
		}

		$response = $this->license->validate_license( $request_args );

		wp_send_json( $response );
	}

	/**
	 * Add a message to be shown in admin notices
	 *
	 * @param string $message The message to be added.
	 * @param string $type    The type of message.
	 * @param string $key     The message key.
	 * @return array
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function add_message( $message, $type = 'success', $key = '' ) {
		$this->messages[ $type ][] = array(
			'key'     => $key,
			'message' => $message,
			'type'    => $type,
		);

		return $this->messages;
	}

	
}