<?php
/**
 * The Software License Server for WooCommerce Client Library
 *
 * This class defines all code necessary to check for a valid license and available updates stored on your Software License Server for WooCommerce.
 *
 * Documentation can be found here : https://licenseserver.io/documentation
 *
 * @version 1.0.0
 * @since   1.0.0
 * @package SLSWC_Client
 * @link    https://licenseserver.io/
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
class LicenseClient {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	private static $instances = array();

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
	 * The domain the plugin is running on.
	 *
	 * @var string $version
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	private $domain;

	/**
	 * The current environment on which the client is install.
	 *
	 * @var     string
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	private $environment;

	/**
	 * Holds instance of Manager class
	 *
	 * @var     Manager
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public $client_manager;

	/**
	 * Return an instance of this class.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @param   string $license_server_url - The base url to your woocommerce shop.
	 * @param   string $base_file - path to the plugin file or directory, relative to the plugins directory.
	 * @param   string $software_type - the type of software this is. plugin|theme, default: plugin.
	 * @param   mixed  ...$args - array of additional arguments to override default ones.
	 * @return  object A single instance of this class.
	 */
	public static function get_instance( $license_server_url, $base_file, $software_type = 'plugin', ...$args ) {

		$args = recursive_parse_args( $args, recursive_parse_args( self::get_default_args(), self::get_file_information( $base_file, $software_type ) ) );

		$text_domain = $args['text_domain'];
		if ( ! array_key_exists( $text_domain, self::$instances ) ) {
			self::$instances[ $text_domain ] = new self( $license_server_url, $base_file, $software_type, $args );
		}

		return self::$instances;

	} // get_instance

	/**
	 * Initialize the class actions.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @param   string $license_server_url - The base url to your woocommerce shop.
	 * @param   string $base_file - path to the plugin file or directory, relative to the plugins directory.
	 * @param   string $software_type - the type of software this is. plugin|theme, default: plugin.
	 * @param   array  $args - array of additional arguments to override default ones.
	 */
	private function __construct( $license_server_url, $base_file, $software_type, $args ) {
		if ( empty( $args ) ) {
			$args = $this->get_file_information( $base_file, $software_type );
		}

		$this->base_file = $base_file;
		$this->name      = empty( $args['name'] ) ? $args['title'] : $args['name'];

		$this->version     = $args['version'];
		$this->text_domain = $args['text_domain'];

		if ( 'plugin' === $software_type ) {
			$this->plugin_file = plugin_basename( $base_file );
			$this->slug        = empty( $args['slug'] ) ? basename( $this->plugin_file, '.php' ) : $args['slug'];
		} else {
			$this->theme_file = $base_file;
			$this->slug       = empty( $args['slug'] ) ? basename( $this->theme_file, '.css' ) : $args['slug'];
		}

		$this->license_server_url = apply_filters( 'slswc_license_server_url_for_' . $this->slug, trailingslashit( $license_server_url ) );

		$this->update_interval = $args['update_interval'];
		$this->debug           = apply_filters( 'slswc_client_logging', defined( 'WP_DEBUG' ) && WP_DEBUG ? true : $args['debug'] );

		$this->option_name   = $this->slug . '_license_manager';
		$this->domain        = untrailingslashit( str_ireplace( array( 'http://', 'https://' ), '', home_url() ) );
		$this->software_type = $software_type;
		$this->environment   = $args['environment'];

		$default_license_options = $this->get_default_license_options();
		$this->license_details   = get_option( $this->option_name, $default_license_options );

		$this->license_manager_url = esc_url( admin_url( 'options-general.php?page=slswc_license_manager&tab=licenses' ) );

		// Get the license server host.
		// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
		$license_server_host = @wp_parse_url( $this->license_server_url, PHP_URL_HOST );
		//phpcs:enable
		// phpcs:ignore
		$this->license_server_host = apply_filters( 'slswc_license_server_host_for_' . $this->slug, $license_server_host);

		// Don't run the license activation code if running on local host.
		$host = isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : '';

		if ( ClientManager::is_dev( $host, $this->environment ) && ( ! empty( $args['debug'] ) && ! $args['debug'] ) ) {

			add_action( 'admin_notices', array( $this, 'license_localhost' ) );

		} else {

			// Initialize wp-admin interfaces.
			add_action( 'admin_init', array( $this, 'check_install' ) );

			// Internal methods.
			add_filter( 'http_request_host_is_external', array( $this, 'fix_update_host' ), 10, 2 );

			// Validate license on save.
			add_action( 'slswc_save_license_' . $this->slug, array( $this, 'validate_license' ), 99 );

			/**
			 * Only allow updates if they have a valid license key.
			 * Or API keys are set to check for updates.
			 */
			if ( 'active' === $this->license_details['license_status'] || 'expiring' === $this->license_details['license_status'] || ClientManager::is_connected() ) {
				if ( 'plugin' === $this->software_type ) {
					add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_check' ) );
					add_filter( 'plugins_api', array( $this, 'add_plugin_info' ), 10, 3 );
					add_filter( 'plugin_row_meta', array( $this, 'check_for_update_link' ), 10, 2 );
				} else {
					add_action( 'pre_set_site_transient_update_themes', array( $this, 'theme_update_check' ), 21, 1 );
				}

				add_action( 'admin_init', array( $this, 'process_manual_update_check' ) );
				add_action( 'all_admin_notices', array( $this, 'output_manual_update_check_result' ) );
			}
		}

		global $slswc_license_server_url, $slswc_slug, $slswc_text_domain, $slswc_products;
		$slswc_license_server_url = trailingslashit( $license_server_url );
		$slswc_slug               = $args['slug'];
		$slswc_text_domain        = $args['text_domain'];

		$slswc_products = get_transient( 'slswc_products' );

		$slswc_products[ $slswc_slug ] = array(
			'slug'               => $slswc_slug,
			'text_domain'        => $slswc_text_domain,
			'license_server_url' => $slswc_license_server_url,
		);

		$slswc_products = array_filter( $slswc_products );

		set_transient( 'slswc_products', $slswc_products, HOUR_IN_SECONDS );

		Logger::log( "License Server Url: $license_server_url" );
		Logger::log( "Base file: $base_file" );
		Logger::log( "Software type: $software_type" );
		Logger::log( $args );
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
			'environment'     => ClientManager::get_environment(),
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
		echo sprintf( __( 'The %1$s license key has not been activated, so you will not be able to get automatic updates or support! %2$sClick here%3$s to activate your support and updates license key.', 'slswcclient' ), esc_attr( $this->name ), '<a href="' . esc_url_raw( $this->license_manager_url ) . '">', '</a>' );
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
	 * @param string $action Action to be taken. Possible balues: activate|deactivate|check_update. Default: check_update.
	 * @param array  $request_info The data to be sent as part of the request.
	 */
	public function server_request( $action = 'check_update', $request_info = array() ) {
		Logger::log( "Server request $action with license key: {$this->license_details['license_key']}" );

		if ( empty( $request_info ) && ! ClientManager::is_connected() ) {
			$request_info['slug']        = $this->slug;
			$request_info['license_key'] = trim( $this->license_details['license_key'] );
			$request_info['domain']      = $this->domain;
			$request_info['version']     = $this->version;
			$request_info['environment'] = $this->environment;
		} elseif ( ClientManager::is_connected() ) {
			$request_info['slug']        = $this->slug;
			$request_info['domain']      = $this->domain;
			$request_info['version']     = $this->version;
			$request_info['environment'] = $this->environment;

			$request_info = array_merge( $request_info, ClientManager::get_api_keys() );
		}

		return ClientManager::server_request( $action, $request_info );

	} // server_request


	/**
	 * Validate the license is active and if not, set the status and return false
	 *
	 * @since 1.0.0
	 * @param object $response_body Response body.
	 */
	public function check_license( $response_body ) {

		$status = $response_body->status;

		if ( 'active' === $status || 'expiring' === $status ) {
			return true;
		}

		$this->set_license_status( $status );
		$this->set_license_expires( $response_body->expires );
		$this->save();

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

		if ( strtolower( $host ) === strtolower( $this->license_server_url ) ) {
			return true;
		}
		return $allow;

	} //fix_update_host

	/**
	 * License page output call back function.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function load_license_page() {
		?>
<div class='wrap'>
		<?php // translators: 1 - Plugin/Theme name. ?>
<h2><?php echo esc_attr( sprintf( __( '%s License Manager', 'slswcclient' ), esc_attr( $this->name ) ) ); ?></h2>
<form action='options.php' method='post'>
	<div class="main">
		<div class="notice update">
		<?php printf( esc_attr( __( 'Please Note: If your license is active on another website you will need to deactivate this before being able to activate it on this site. IMPORTANT: If this is a development or a staging site dont activate your license.  Your license should ONLY be activated on the LIVE WEBSITE you use Pro on.', 'slswcclient' ) ), esc_attr( $this->name ) ); ?>
		</div>

		<?php settings_errors( $this->option_name ); ?>

		<?php
			settings_fields( $this->option_name );
			do_settings_sections( $this->option_name );
			submit_button( __( 'Save Changes', 'slswcclient' ) );
		?>
		</div>
	</form>
</div>

		<?php
	} // license_page

	/**
	 * License activation settings section callback
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function license_activation_section_callback() {

		echo '<p>' . esc_attr( __( 'Please enter your license key to activate automatic updates and verify your support.', 'slswcclient' ) ) . '</p>';

	} // license_activation_section_callback

	/**
	 * License key field callback
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function license_key_field() {
		$value = ( isset( $this->license_details['license_key'] ) ) ? $this->license_details['license_key'] : '';
		echo '<input type="text" id="license_key" name="' . esc_attr( $this->option_name ) . '[license_key]" value="' . esc_attr( trim( $value ) ) . '" />';

	} // license_key_field

	/**
	 * License acivated field
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function license_status_field() {

		$license_labels = $this->license_status_types();

		echo esc_attr( $license_labels[ $this->license_details['license_status'] ] );

	} // license_status_field

	/**
	 * License acivated field
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function license_expires_field() {
		echo esc_attr( $this->license_details['license_expires'] );
	}

	/**
	 * License deactivate checkbox
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function license_deactivate_field() {

		echo '<input type="checkbox" id="deactivate_license" name="' . esc_attr( $this->option_name ) . '[deactivate_license]" />';

	} // license_deactivate_field

	/**
	 * The current server environment
	 *
	 * @return  void
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function licence_environment_field() {
		echo '<input type="checkbox" id="environment" name="' . esc_attr( $this->option_name ) . '[environment]" />';
	}

	/**
	 * Validate the license key information sent from the form.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param array $input the input passed from the request.
	 */
	public function validate_license( $input ) {

		$options = $this->license_details;
		$type    = null;
		$message = null;
		$expires = '';

		$environment   = isset( $input['environment'] ) ? $input['environment'] : 'live';
		$active_status = $this->get_active_status( $environment );

		Logger::log( "Validate license:: key={$input['license_key']}, environment=$environment, status=$active_status" );

		foreach ( $options as $key => $value ) {

			if ( 'license_key' === $key ) {

				if ( $active_status && 'active' === $this->get_license_status() ) {
					continue;
				}

				if ( ! array_key_exists( 'deactivate_license', $input ) && ! $active_status ) {

					$this->license_details['license_key'] = $input[ $key ];
					$this->environment                    = $environment;
					$response                             = $this->server_request( 'activate' );

					Logger::log( 'Activating. current status is: ' . $this->get_license_status() );
					Logger::log( $response );

					// phpcs:ignore
					if ( null !== $response ) {

						if ( ClientManager::check_response_status( $response ) ) {

							$options[ $key ]                          = $input[ $key ];
							$options['license_status']                = $response->status;
							$options['license_expires']               = $response->expires;
							$options['active_status'][ $environment ] = 'yes';

							if ( 'valid' === $response->status || 'active' === $response->status ) {
								$type    = 'updated';
								$message = __( 'License activated.', 'slswcclient' );
							} else {
								$type     = 'error';
								$messages = $this->license_status_types();
								$message  = $messages[ $response->status ];
							}
						} else {

							$type    = 'error';
							$message = __( 'Invalid License', 'slswcclient' );
						}

						Logger::log( $message );

						add_settings_error(
							$this->option_name,
							esc_attr( 'settings_updated' ),
							$message,
							$type
						);

						$options[ $key ] = $input[ $key ];
					}
				}

				$options[ $key ] = $input[ $key ];

			} elseif ( array_key_exists( $key, $input ) && 'deactivate_license' === $key && $active_status ) {
				$this->environment = $environment;
				$response          = $this->server_request( 'deactivate' );

				Logger::log( $response );

				if ( null !== $response ) {

					if ( ClientManager::check_response_status( $response ) ) {
						$options[ $key ]                          = $input[ $key ];
						$options['license_status']                = $response->status;
						$options['license_expires']               = $response->expires;
						$options['active_status'][ $environment ] = 'no';
						$type                                     = 'updated';
						$message                                  = __( 'License Deactivated', 'slswcclient' );

					} else {

						$type    = 'updated';
						$message = __( 'Unable to deactivate license. Please deactivate on the store.', 'slswcclient' );

					}

					Logger::log( $message );

					add_settings_error(
						$this->option_name,
						esc_attr( 'settings_updated' ),
						$message,
						$type
					);
				}
			} elseif ( 'license_status' === $key ) {

				if ( empty( $options['license_status'] ) ) {
					$options['license_status'] = 'inactive';
				} else {
					$options['license_status'] = $options['license_status'];
				}
			} elseif ( 'license_expires' === $key ) {

				if ( empty( $options['license_expires'] ) ) {
					$options['license_expires'] = '';
				} else {
					$options['license_expires'] = gmdate( 'Y-m-d', strtotime( $options['license_expires'] ) );
				}
			} elseif ( 'environment' === $key ) {
				$options['environment'] = $input['environment'];
			}
		}

		update_option( $this->option_name, $options );

		Logger::log( $options );

		return $options;

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
	 * Get theme or plugin information from file.
	 *
	 * @param   string $base_file - Plugin file or theme slug.
	 * @param   string $type - Product type. plugin|theme.
	 * @return  array
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public static function get_file_information( $base_file, $type = 'plugin' ) {
		$data = array();
		if ( 'plugin' === $type ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$plugin = get_plugin_data( $base_file, false );

			$data = array(
				'name'              => $plugin['Name'],
				'title'             => $plugin['Title'],
				'description'       => $plugin['Description'],
				'author'            => $plugin['Author'],
				'author_uri'        => $plugin['AuthorURI'],
				'version'           => $plugin['Version'],
				'plugin_url'        => $plugin['PluginURI'],
				'text_domain'       => $plugin['TextDomain'],
				'domain_path'       => $plugin['DomainPath'],
				'network'           => $plugin['Network'],

				// SLSWC Headers.
				'slswc'             => ! empty( $plugin['SLSWC'] ) ? $plugin['SLSWC'] : '',
				'slug'              => ! empty( $plugin['Slug'] ) ? $plugin['Slug'] : $plugin['TextDomain'],
				'required_wp'       => ! empty( $plugin['RequiredWP'] ) ? $plugin['RequiredWP'] : '',
				'compatible_to'     => ! empty( $plugin['CompatibleTo'] ) ? $plugin['CompatibleTo'] : '',
				'documentation_url' => ! empty( $plugin['DocumentationURL'] ) ? $plugin['DocumentationURL'] : '',
				'type'              => $type,
			);
		} elseif ( 'theme' === $type ) {
			if ( ! function_exists( 'wp_get_theme' ) ) {
				require_once ABSPATH . 'wp-includes/theme.php';
			}
			$theme = wp_get_theme( basename( $base_file ) );

			$data = array(
				'name'              => $theme->get( 'Name' ),
				'theme_url'         => $theme->get( 'ThemeURI' ),
				'description'       => $theme->get( 'Description' ),
				'author'            => $theme->get( 'Author' ),
				'author_uri'        => $theme->get( 'AuthorURI' ),
				'version'           => $theme->get( 'Version' ),
				'template'          => $theme->get( 'Template' ),
				'status'            => $theme->get( 'Status' ),
				'tags'              => $theme->get( 'Tags' ),
				'text_domain'       => $theme->get( 'TextDomain' ),
				'domain_path'       => $theme->get( 'DomainPath' ),
				// SLSWC Headers.
				'slswc'             => ! empty( $theme->get( 'SLSWC' ) ) ? $theme->get( 'SLSWC' ) : '',
				'slug'              => ! empty( $theme->get( 'Slug' ) ) ? $theme->get( 'Slug' ) : $theme->get( 'TextDomain' ),
				'required_wp'       => ! empty( $theme->get( 'RequiredWP' ) ) ? $theme->get( 'RequiredWP' ) : '',
				'compatible_to'     => ! empty( $theme->get( 'CompatibleTo' ) ) ? $theme->get( 'CompatibleTo' ) : '',
				'documentation_url' => ! empty( $theme->get( 'DocumentationURL' ) ) ? $theme->get( 'DocumentationURL' ) : '',
				'type'              => $type,
			);
		}

		return $data;

	}

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

