<?php
/**
 * Defines the license manager client class for SLSWC
 *
 * @version     1.0.2
 * @since       1.0.2
 * @package     Client
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
class Client {
	/**
	 * Instance of this class.
	 *
	 * @var Madvault\Slswc\Client\Client
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
	 * Theme file.
	 *
	 * @var string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $theme_file = '';

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
	private function __construct( $license_server_url, $base_file, $software_type = 'plugin', ...$args ) {
		$args = Helper::recursive_parse_args(
			$args,
			Helper::recursive_parse_args(
				self::get_default_args(),
				Helper::get_file_information(
					$base_file,
					$software_type
				)
			)
		);

		$this->args = $args;

		if ( 'plugin' === $software_type ) {
			$this->plugin_file = plugin_basename( $base_file );
			$this->slug        = empty( $args['slug'] ) ? basename( $this->plugin_file, '.php' ) : $args['slug'];
		} else {
			$this->theme_file = $base_file;
			$this->slug       = empty( $args['slug'] ) ? basename( $this->theme_file, '.css' ) : $args['slug'];
		}

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

		$this->option_name   = $this->slug . '_license_manager';
		$this->domain        = untrailingslashit( str_ireplace( array( 'http://', 'https://' ), '', home_url() ) );
		$this->software_type = $software_type;
		$this->environment   = isset( $args['environment'] ) ? $args['environment'] : '';

		$default_license_options = $this->get_default_license_options();
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
	 * Initialize action hooks and filters
	 *
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function init_hooks() {
		// Don't run the license activation code if running on local host.
		$host = isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : '';
		if ( Helper::is_dev( $host, $this->environment ) && ( ! empty( $this->args['debug'] ) && ! $this->args['debug'] ) ) {

			add_action( 'admin_notices', array( $this, 'license_localhost' ) );

		} else {

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

			if ( in_array( $license_status, $allowed_statuses ) || Helper::is_connected() ) {
				if ( 'plugin' === $this->software_type ) {
					add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_check' ) );
					add_filter( 'plugins_api', array( $this, 'add_plugin_info' ), 10, 3 );
					add_filter( 'plugin_row_meta', array( $this, 'check_for_update_link' ), 10, 2 );
				} else {
					add_filter( 'pre_set_site_transient_update_themes', array( $this, 'theme_update_check' ), 21, 1 );
				}

				add_action( 'admin_init', array( $this, 'process_manual_update_check' ) );
				add_action( 'all_admin_notices', array( $this, 'output_manual_update_check_result' ) );
			}
		}
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
	 * Get default license options.
	 *
	 * @param array $args Options to override the defaults.
	 * @return  array
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function get_default_license_options( $args = array() ) {
		$default_options = array(
			'license_status'     => 'inactive',
			'license_key'        => '',
			'license_expires'    => '',
			'current_version'    => $this->version,
			'environment'        => $this->environment,
			'active_status'      => array(
				'live'    => 'no',
				'staging' => 'no',
			),
			'deactivate_license' => 'deactivate_license',
		);

		if ( ! empty( $args ) ) {
			$default_options = wp_parse_args( $args, $default_options );
		}

		return apply_filters( 'slswc_client_default_license_options', $default_options );
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
			$default_license_options = $this->get_default_license_options();
			update_option( $this->option_name, $default_license_options );
		}

		if ( '' === $this->license_details || 'inactive' === $this->license_details['license_status'] || 'deactivated' === $this->license_details['license_status'] ) {
			add_action( 'admin_notices', array( $this, 'license_inactive' ) );
		}

		if ( 'expired' === $this->license_details['license_status'] && 'active' === $this->license_details['license_status'] ) {
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
		echo esc_attr( sprintf( __( '%s has detected you are running on your localhost. The license activation system has been disabled. ', 'slswcclient' ), esc_attr( $this->name ) ) ) . '</p></div>';

	}

	/**
	 * Check for updates with the license server.
	 *
	 * @since  1.0.0
	 * @param  object $transient object from the update api.
	 * @return object $transient object possibly modified.
	 */
	public function update_check( $transient ) {

		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$server_response = $this->server_request( 'check_update' );

		if ( $this->check_license( $server_response ) ) {
			if ( isset( $server_response ) && is_object( $server_response->software_details ) ) {

				$plugin_update_info = $server_response->software_details;

				if ( isset( $plugin_update_info->new_version ) ) {
					if ( version_compare( $plugin_update_info->new_version, $this->version, '>' ) ) {
						// Required to cast as array due to how object is returned from api.
						$plugin_update_info->sections              = (array) $plugin_update_info->sections;
						$plugin_update_info->banners               = (array) $plugin_update_info->banners;
						$transient->response[ $this->plugin_file ] = $plugin_update_info;
					}
				}
			}
		}

		return $transient;

	}

	/**
	 * Check if there are updates for themes.
	 *
	 * @param   mixed $transient transient object from update api.
	 * @return  mixed $transient transient object from update api.
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function theme_update_check( $transient ) {

		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$server_response = $this->server_request( 'check_update' );

		if ( $this->check_license( $server_response ) ) {

			if ( isset( $server_response ) && is_object( $server_response->software_details ) ) {

				$theme_update_info = $server_response->software_details;

				if ( isset( $theme_update_info->new_version ) ) {
					if ( version_compare( $theme_update_info->new_version, $this->version, '>' ) ) {
						// Required to cast as array due to how object is returned from api.
						$theme_update_info->sections = (array) $theme_update_info->sections;
						$theme_update_info->banners  = (array) $theme_update_info->banners;
						$theme_update_info->url      = $theme_update_info->homepage;
						// Theme name.
						$transient->response[ $this->slug ] = (array) $theme_update_info;
					}
				}
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

		$server_response    = $this->server_request();
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
	 * Send a request to the server
	 *
	 * @param string $action Action to be taken. Possible values: activate|deactivate|check_update. Default: check_update.
	 * @param array  $request_info The data to be sent as part of the request.
	 *
	 * @version 1.1.0
	 * @since   1.1.0 - Use helper class.
	 */
	public function server_request( $action = 'check_update', $request_info = array() ) {
		Helper::log( "Server request $action with license key: {$this->license_details['license_key']}" );

		if ( empty( $request_info ) && ! Helper::is_connected() ) {
			$request_info['slug']        = $this->slug;
			$request_info['license_key'] = trim( $this->license_details['license_key'] );
			$request_info['domain']      = $this->domain;
			$request_info['version']     = $this->version;
			$request_info['environment'] = $this->environment;
		} elseif ( Helper::is_connected() ) {
			$request_info['slug']        = $this->slug;
			$request_info['domain']      = $this->domain;
			$request_info['version']     = $this->version;
			$request_info['environment'] = $this->environment;

			$request_info = array_merge( $request_info, Helper::get_api_keys() );
		}

 		return Helper::server_request( $this->license_server_url, $action, $request_info );
	}


	/**
	 * Validate the license is active and if not, set the status and return false
	 *
	 * @since 1.0.0
	 * @param object $response_body Response body.
	 */
	public function check_license( $response_body ) {

		$status = is_array( $response_body) ? $response_body['status'] : $response_body->status;

		if ( 'active' === $status || 'expiring' === $status ) {
			return true;
		}

		if ( ! is_numeric( $status ) ) {
			$this->set_license_status( $status );
			$this->set_license_expires( $response_body->expires );
			$this->save();
		}

		return false;

	} // check_license


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
		if ( $file === $this->plugin_file && current_user_can( 'update_plugins' ) ) {

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

			$update_link_text = apply_filters( 'slswc_update_link_text_' . $this->slug, __( 'Check for updates', 'slswcclient' ) );

			if ( ! empty( $update_link_text ) ) {
				$links[] = sprintf( '<a href="%s">%s</a>', esc_attr( $update_link_url ), $update_link_text );
			}
		}

		return $links;

	} // check_for_update_link

	/**
	 * Process the manual check for update if check for update is clicked on the plugins page.
	 *
	 * @since 1.0.0
	 */
	public function process_manual_update_check() {
		// phpcs:ignore
		if ( isset( $_GET['slswc_check_for_update'] ) && isset( $_GET['slswc_slug'] ) && $_GET['slswc_slug'] === $this->slug && current_user_can( 'update_plugins' ) && check_admin_referer( 'slswc_check_for_update' ) ) {

			// Check for updates.
			$server_response = $this->server_request();

			if ( $this->check_license( $server_response ) ) {

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
		}

	} // process_manual_update_check


	/**
	 * Out the results of the manual check
	 *
	 * @since 1.0.0
	 */
	public function output_manual_update_check_result() {

		// phpcs:ignore
		if ( isset( $_GET['slswc_update_check_result'] ) && isset( $_GET['slswc_slug'] ) && ( $_GET['slswc_slug'] === $this->slug ) ) {

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

			printf( '<div class="updated notice is-dismissible"><p>%s</p></div>', esc_attr( apply_filters( 'slswc_manual_check_message_result_' . $this->slug, $admin_notice, $check_result ) ) );
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
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'activate-license-' . esc_attr( sanitize_text_field( wp_unslash( $_POST['slug'] ) ) ) ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security verification failed. Please reload the page and try again.', 'slswcclient' ),
				)
			);
		}

		$request_args = array (
			'slug' => isset( $_POST['slug'] ) && ! empty( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '',
			'license_key' => isset( $_POST['license_key'] ) && ! empty( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '',
			'domain'      => isset( $_POST['domain'] ) && ! empty( $_POST['domain'] ) ? sanitize_text_field( wp_unslash( $_POST['domain'] ) ) : '',
			'version'     => isset( $_POST['version'] ) && ! empty( $_POST['version'] ) ? sanitize_text_field( wp_unslash( $_POST['version'] ) ) : '',
			'environment' => isset( $_POST['environment'] ) && ! empty( $_POST['environment'] ) ? sanitize_text_field( wp_unslash( $_POST['environment'] ) ) : '',
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

		$license_action = isset( $_POST['license_action'] ) && ! empty( $_POST['license_action'] ) ? sanitize_text_field( wp_unslash( $_POST['license_action'] ) ) : '';
		if ( $license_action == 'deactivate' ) {
			$request_args['deactivate_license' ] = 'yes'; 
		}

		$response = $this->validate_license( $request_args );

		wp_send_json( $response );
	}

	/**
	 * Validate the license key information sent from the form.
	 *
	 * @since   1.0.0
	 * @version 1.0.2
	 * @param array $input the input passed from the request.
	 */
	public function validate_license( $input ) {
		$options = $this->license_details;
		$type    = null;
		$message = null;

		// Reset the license data if the license key has changed.
		if ( $options['license_key'] !== $input['license_key'] ) {
			$options               = self::get_default_license_options();
			$this->license_details = $options;
		}

		$environment   = isset( $input['environment'] ) ? $input['environment'] : '';
		$active_status = $this->get_active_status( $environment );

		$this->environment                    = $environment;
		$this->license_details['license_key'] = $input['license_key'];
		$options                              = wp_parse_args( $input, $options );

		Helper::log( "Validate license:: key={$input['license_key']}, environment=$environment, status=$active_status" );

		$response = null;
		$action   = array_key_exists( 'deactivate_license', $input ) ? 'deactivate' : 'activate';

		if ( $active_status && 'activate' === $action ) {
			$options['license_status'] = 'active';
		}

		if ( 'activate' === $action && ! $active_status ) {
			Helper::log( 'Activating. current status is: ' . $this->get_license_status() );

			unset( $options['deactivate_license'] );
			$this->license_details = $options;

			$response = $this->server_request( 'activate' );
		} elseif ( 'deactivate' === $action ) {
			Helper::log( 'Deactivating license. current status is: ' . $this->get_license_status() );

			$response = $this->server_request( 'deactivate' );
		} else {
			unset( $options['deactivate_license'] );
			$this->license_details = $options;

			$response = $this->server_request( 'check_license' );
		}

		if ( is_null( $response ) ) {
			$message = __( 'Error: Your license might be invalid or there was an unknown error on the license server. Please try again and contact support if this issue persists.', 'slswcclient' );
			ClientManager::add_message(
				'error',
				$message,
				$type
			);
			update_option( $this->option_name, $options );
			return array (
				'status'   => 'bad_request',
				'message'  => $message,
				'response' => $response
			);
		}

		// phpcs:ignore
		if ( ! Helper::check_response_status( $response ) ) {
			update_option( $this->option_name, $options );
			return array(
				'status'   => 'invalid',
				'message'  => $response['response'],
				'response' => $response,
			);
		}

		$options['license_key']                   = $input['license_key'];
		$options['license_status']                = $response->domain->status;
		$options['domain']                        = $response->domain;
		$options['license_expires']               = $response->expires;
		$options['active_status'][ $environment ] = 'activate' === $action && 'active' === $response->domain->status ? 'yes' : 'no';

		$domain_status = $response->domain->status;

		$type     = 'updated';
		$messages = $this->license_status_types();

		if ( ( 'valid' === $domain_status || 'active' === $domain_status ) && 'activate' === $action ) {
			$message = __( 'License activated.', 'slswcclient' );
		} elseif ( 'active' !== $domain_status && 'activate' === $action ) {
			$type    = 'error';
			$message = sprintf(
				__( 'Failed to activate license. %s', 'slswcclient' ),
				$messages[ $domain_status ]
			);
		} elseif ( 'deactivate' === $action && 'deactivated' === $domain_status ) {
			$message = __( 'License Deactivated', 'slswcclient' );
		} elseif ( 'deactivate' === $action && 'deactivate' !== $domain_status ) {
			$type    = 'error';
			$message = sprintf(
				// translators: %s - The message describing the license status.
				__( 'Unable to deactivate license. Please deactivate on the store. %s', 'slswcclient' ),
				$messages[ $domain_status ]
			);
		} else {
			$type    = 'error';
			$message = $messages[ $response->status ];
		}

		Helper::log( $message );

		ClientManager::add_message(
			$this->option_name,
			$message,
			$type
		);

		update_option( $this->option_name, $options );

		Helper::log( $options );

		return array(
			'message'  => $message,
			'options'  => $options,
			'status'   => $domain_status,
			'response' => $response
		);
	} // validate_license

	/**
	 * Check if staging activated
	 *
	 * @param string $environment environment to get status.
	 * @return boolean
	 */
	public function get_active_status( $environment ) {
		$options = $this->license_details;
		if ( ! isset( $options['active_status'] ) ) {
			$options['active_status'] = array(
				'live'    => false,
				'staging' => false,
			);
		}
		$active_status = $options['active_status'][ $environment ];
		return is_bool( $active_status ) ? $active_status : ( 'yes' === strtolower( $active_status ) || 1 === $active_status || 'true' === strtolower( $active_status ) || '1' === $active_status );
	}
	/**
	 * The available license status types.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function license_status_types() {

		return apply_filters(
			'slswc_license_status_types',
			array(
				'valid'           => __( 'Valid', 'slswcclient' ),
				'deactivated'     => __( 'Deactivated', 'slswcclient' ),
				'max_activations' => __( 'Max Activations reached', 'slswcclient' ),
				'invalid'         => __( 'Invalid', 'slswcclient' ),
				'inactive'        => __( 'Inactive', 'slswcclient' ),
				'active'          => __( 'Active', 'slswcclient' ),
				'expiring'        => __( 'Expiring', 'slswcclient' ),
				'expired'         => __( 'Expired', 'slswcclient' ),
			)
		);

	} // software_types


	/**
	 * --------------------------------------------------------------------------
	 * Getters
	 * --------------------------------------------------------------------------
	 *
	 * Methods for getting object properties.
	 */

	/**
	 * Get the license status.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function get_license_status() {

		return $this->license_details['license_status'];

	} // get_license_status

	/**
	 * Get the license key
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function get_license_key() {

		return $this->license_details['license_key'];

	} // get_license_key


	/**
	 * Get the license expiry
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function get_license_expires() {

		return $this->license_details['license_expires'];

	} // get_license_expires

	

	/**
	 * --------------------------------------------------------------------------
	 * Setters
	 * --------------------------------------------------------------------------
	 *
	 * Methods to set the object properties for this instance. This does not
	 * interact with the database.
	 */

	/**
	 * Set the license status
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $license_status license status.
	 */
	public function set_license_status( $license_status ) {

		$this->license_details['license_status'] = $license_status;

	} // set_license_status

	/**
	 * Set the license key
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $license_key License key.
	 */
	public function set_license_key( $license_key ) {

		$this->license_details['license_key'] = $license_key;

	} // set_license_key

	/**
	 * Set the license expires.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $license_expires License expiry date.
	 */
	public function set_license_expires( $license_expires ) {

		$this->license_details['license_expires'] = $license_expires;

	} // set_license_expires

	/**
	 * Save the license details.
	 *
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function save() {

		update_option( $this->option_name, $this->license_details );

	} // save
} 