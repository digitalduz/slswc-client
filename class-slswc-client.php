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
 * @version     1.0.2
 * @since       1.0.0
 * @package     SLSWC_Client
 * @link        https://licenseserver.io/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'SLSWC_Client' ) ) :

	/**
	 * Class responsible for a single product.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	//phpcs:ignore
	class SLSWC_Client {
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
		 * License URL - The base URL for your woocommerce install
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
		 * Holds instance of SLSWC_Client_Manager class
		 *
		 * @var     SLSWC_Client_Manager
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

			if ( SLSWC_Client_Manager::is_dev( $host, $this->environment ) && ( ! empty( $args['debug'] ) && ! $args['debug'] ) ) {

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
				if ( 'active' === $this->license_details['license_status'] || 'expiring' === $this->license_details['license_status'] || SLSWC_Client_Manager::is_connected() ) {
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
			$slswc_license_server_url = trailingslashit( $this->license_server_url );
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

			SLSWC_Client_Manager::log( "License Server Url: $this->license_server_url" );
			SLSWC_Client_Manager::log( "Base file: $base_file" );
			SLSWC_Client_Manager::log( "Software type: $software_type" );
			SLSWC_Client_Manager::log( $args );
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
				'environment'     => SLSWC_Client_Manager::get_environment(),
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
			SLSWC_Client_Manager::log( "Server request $action with license key: {$this->license_details['license_key']}" );

			if ( empty( $request_info ) && ! SLSWC_Client_Manager::is_connected() ) {
				$request_info['slug']        = $this->slug;
				$request_info['license_key'] = trim( $this->license_details['license_key'] );
				$request_info['domain']      = $this->domain;
				$request_info['version']     = $this->version;
				$request_info['environment'] = $this->environment;
			} elseif ( SLSWC_Client_Manager::is_connected() ) {
				$request_info['slug']        = $this->slug;
				$request_info['domain']      = $this->domain;
				$request_info['version']     = $this->version;
				$request_info['environment'] = $this->environment;

				$request_info = array_merge( $request_info, SLSWC_Client_Manager::get_api_keys() );
			}

			return SLSWC_Client_Manager::server_request( $action, $request_info );

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

			if ( strtolower( $host ) === strtolower( $this->license_server_host ) ) {
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
		 * @since   1.0.0
		 * @version 1.0.2
		 * @param array $input the input passed from the request.
		 */
		public function validate_license( $input ) {

			$options = $this->license_details;
			$type    = null;
			$message = null;

			// Reset the license data if the license key has changed.
			if ( $options['license_key'] === $input['license_key'] ) {
				$options               = self::get_default_license_options();
				$this->license_details = $options;
			}

			$environment   = isset( $input['environment'] ) ? $input['environment'] : 'live';
			$active_status = $this->get_active_status( $environment );

			$this->environment                    = $environment;
			$this->license_details['license_key'] = $input['license_key'];
			$options                              = wp_parse_args( $input, $options );

			SLSWC_Client_Manager::log( "Validate license:: key={$input['license_key']}, environment=$environment, status=$active_status" );

			$response = null;
			$action   = array_key_exists( 'deactivate_license', $input ) ? 'deactivate' : 'activate';

			if ( $active_status && 'activate' === $action ) {
				$options['license_status'] = 'active';
			}

			if ( 'activate' === $action && ! $active_status ) {
				SLSWC_Client_Manager::log( 'Activating. current status is: ' . $this->get_license_status() );

				unset( $options['deactivate_license'] );
				$this->license_details = $options;

				$response = $this->server_request( 'activate' );
			} elseif ( 'deactivate' === $action ) {
				SLSWC_Client_Manager::log( 'Deactivating license. current status is: ' . $this->get_license_status() );

				$response = $this->server_request( 'deactivate' );
			} else {
				unset( $options['deactivate_license'] );
				$this->license_details = $options;

				$response = $this->server_request( 'check_license' );
			}

			if ( is_null( $response ) ) {
				SLSWC_Client_Manager::add_message(
					'error',
					__( 'Error: Your license might be invalid or there was an unknown error on the license server. Please try again and contact support if this issue persists.', 'slswcclient' ),
					$type
				);
				update_option( $this->option_name, $options );
				return;
			}

			// phpcs:ignore
			if ( ! SLSWC_Client_Manager::check_response_status( $response ) ) {
				update_option( $this->option_name, $options );
				return;
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

			SLSWC_Client_Manager::log( $message );

			SLSWC_Client_Manager::add_message(
				$this->option_name,
				$message,
				$type
			);

			update_option( $this->option_name, $options );

			SLSWC_Client_Manager::log( $options );
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

	} // SLSWC_Client

endif;

if ( ! class_exists( 'SLSWC_Client_Manager' ) ) :
	/**
	 * Class to manage products relying on the Software License Server for WooCommerce.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */

	// phpcs:ignore
	class SLSWC_Client_Manager {
		/**
		 * Instance of this class.
		 *
		 * @since 1.0.0
		 * @var object
		 */
		private static $instance = null;

		/**
		 * Version - current plugin version.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public static $version;

		/**
		 * License URL - The base URL for your woocommerce install.
		 *
		 * @var string
		 * @since 1.0.0
		 */
		public static $license_server_url;

		/**
		 * The plugin slug to check for updates with the server.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public static $slug;

		/**
		 * Plugin text domain.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public static $text_domain;

		/**
		 * List of locally installed plugins
		 *
		 * @var     array $plugins The list of plugins.
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static $plugins;

		/**
		 * List of locally installed themes.
		 *
		 * @var     array $themes The list of themes.
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static $themes;

		/**
		 * List of products
		 *
		 * @var     array
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public static $products;

		/**
		 * Status update messages
		 *
		 * @var array
		 * @version 1.0.1
		 * @since   1.0.1
		 */
		public static $messages = array();

		/**
		 * Return instance of this class
		 *
		 * @param   string $license_server_url The url to the license server.
		 * @param   string $slug The software slug.
		 * @param   string $text_domain The software text domain.
		 * @return  object
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public static function get_instance( $license_server_url, $slug, $text_domain ) {
			self::$license_server_url = $license_server_url;
			self::$slug               = $slug;
			self::$text_domain        = $text_domain;

			if ( null === self::$instance ) {
				self::$instance = new self( self::$license_server_url, self::$slug, 'slswcclient' );
			}

			return self::$instance;
		} // get_instance

		/**
		 * Initialize the class actions
		 *
		 * @since 1.0.0
		 * @version 1.0.0
		 * @param string $license_server_url - The base url to your woocommerce shop.
		 * @param string $slug - The software slug.
		 * @param string $text_domain - The plugin's text domain.
		 */
		private function __construct( $license_server_url, $slug, $text_domain ) {
			self::$license_server_url = apply_filters( 'slswc_client_manager_license_server_url', $license_server_url );
			self::$slug               = $slug;
			self::$text_domain        = $text_domain;

			self::$plugins = self::get_local_plugins();
			self::$themes  = self::get_local_themes();

			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'wp_ajax_slswc_install_product', array( $this, 'product_background_installer' ) );

			if ( self::is_products_page() ) {
				add_action( 'admin_footer', array( $this, 'admin_footer_script' ) );
				add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
			}
		}

		/**
		 * Enqueue scripts.
		 *
		 * @return  void
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function admin_enqueue_scripts() {
			if ( self::is_products_page() ) {
				wp_enqueue_script( 'thickbox' );
				wp_enqueue_style( 'thickbox' );
			}
		}

		/**
		 * Check if the current page is a product list page.
		 *
		 * @return  boolean
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function is_products_page() {

			$tabs = array( 'plugins', 'themes' );
			$page = 'slswc_license_manager';
			// phpcs:disable
			$is_page = isset( $_GET['page'] ) && $page === $_GET['page'] ? true : false;
			$is_tab  = isset( $_GET['tab'] ) && in_array( wp_unslash( $_GET['tab'] ), $tabs, true ) ? true : false;
			// phpcs:enable
			if ( is_admin() && $is_page && $is_tab ) {
				return true;
			}

			return false;
		}

		/**
		 * Add script to admin footer.
		 *
		 * @return  void
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function admin_footer_script() {
			?>
		<script type="text/javascript">
			jQuery( function( $ ){
				$('.slswc-install-now, .slswc-update-now').on( 'click', function(e){
					e.preventDefault();
					let $el = $(this);
					let download_url = $(this).data('download_url');
					let name = $(this).data('name');
					let slug = $(this).data('slug');
					let type = $(this).data('type');
					let label = $(this).html();
					let nonce = $(this).data('nonce');
					let action_label = "<?php esc_attr_e( 'Processing', 'slswcclient' ); ?>";
					$(this).html('<img src="<?php echo esc_url( admin_url( 'images/loading.gif' ) ); ?>" /> ' + action_label );
					$.ajax({
						url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
						data: {
							action:  'slswc_install_product',
							download_url: download_url,
							name:    name,
							slug:    slug,
							type:    type,
							nonce:   nonce
						},
						dataType: 'json',
						type: 'POST',
						success: function( response ) {
							if ( response.success ) {
								$('#slswc-product-install-message p').html( response.data.message );
								$('#slswc-product-install-message').addClass('updated').show();
							} else {
								$('#slswc-product-install-message p').html( response.data.message );
								$('#slswc-product-install-message').addClass('notice-warning').show();
							}
							$el.html( '<?php echo esc_attr( __( 'Done', 'slswcclient' ) ); ?>' );
							$el.attr('disabled', 'disabled');
						},
						error: function( error ) {
							$('#slswc-product-install-message p').html( error.data.message );
							$('#slswc-product-install-message').addClass('notice-error').show();
						}
					});
				});
			} );
		</script>
			<?php
		}
		/**
		 * ------------------------------------------------------------------
		 * Output Functions
		 * ------------------------------------------------------------------
		 */

		/**
		 * Add the admin menu to the dashboard
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function add_admin_menu() {
			$page = add_options_page(
				__( 'License Manager', 'slswcclient' ),
				__( 'License Manager', 'slswcclient' ),
				'manage_options',
				'slswc_license_manager',
				array( $this, 'show_installed_products' )
			);
		}

		/**
		 * List all products installed on this server.
		 *
		 * @return  void
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public static function show_installed_products() {
			$license_admin_url = admin_url( 'admin.php?page=slswc_license_manager' );
			// phpcs:ignore
			$tab = self::get_tab();

			?>
			<style>
				.slswc-product-thumbnail:before {font-size: 128px;}
				.slswc-plugin-card-bottom {display: flex;}
				.slswc-plugin-card-bottom div {width: 45%;}
				.slswc-plugin-card-bottom div.column-updated {float:left;text-align:left;}
				.slswc-plugin-card-bottom div.column-compatibility {float:right;text-align:right;}
			</style>
			<div class="wrap plugin-install-tab">				
				<div id="slswc-product-install-message" class="notice inline hidden"><p></p></div>
				<h1><?php esc_attr_e( 'Licensed Plugins and Themes', 'slswcclient' ); ?></h1>
				<?php

				if ( isset( $_POST['save_api_keys_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['save_api_keys_nonce'] ) ), 'save_api_keys' ) ) {

					$username        = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
					$consumer_key    = isset( $_POST['consumer_key'] ) ? sanitize_text_field( wp_unslash( $_POST['consumer_key'] ) ) : '';
					$consumer_secret = isset( $_POST['consumer_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['consumer_secret'] ) ) : '';

					$save_username        = update_option( 'slswc_api_username', $username );
					$save_consumer_key    = update_option( 'slswc_consumer_key', $consumer_key );
					$save_consumer_secret = update_option( 'slswc_consumer_secret', $consumer_secret );

					if ( $save_username && $save_consumer_key && $save_consumer_secret ) {
						?>
						<div class="updated"><p><?php esc_attr_e( 'API Settings saved', 'slswcclient' ); ?></p></div>
						<?php
					}
				}

				if ( ! empty( $_POST['connect_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['connect_nonce'] ) ), 'connect' ) ) {
					$connected = self::connect();
					if ( $connected ) {
						?>
						<div class="updated"><p>
						<?php
						esc_attr_e( 'API Connected successfully.', 'slswcclient' );
						?>
						</p></div>
						<?php
					} else {
						?>
						<div class="error notice is-dismissible"><p>
						<?php
						esc_attr_e( 'API connection failed. Please check your keys and try again.', 'slswcclient' );
						?>
						</p></div>
						<?php
					}
				}

				if ( ! empty( $_POST['reset_api_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['reset_api_settings_nonce'] ) ), 'reset_api_settings' ) ) {
					$deleted_username        = delete_option( 'slswc_api_username' );
					$deleted_consumer_key    = delete_option( 'slswc_consumer_key' );
					$deleted_consumer_secret = delete_option( 'slswc_consumer_secret' );

					if ( $deleted_username && $deleted_consumer_key && $deleted_consumer_secret ) {
						?>
						<p class="updated">
						<?php
						esc_attr_e( 'API Keys successfully.', 'slswcclient' );
						?>
						</p>
						<?php
					} else {
						?>
						<p class="updated">
						<?php
						esc_attr_e( 'API Keys not reset.', 'slswcclient' );
						?>
						</p>
						<?php
					}
				}

				if ( ! empty( $_POST['disconnect_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['disconnect_nonce'] ) ), 'disconnect' ) ) {
					update_option( 'slswc_api_connected', 'no' );
				}

				$_all_products = array_merge( self::$plugins, self::$themes );
				foreach ( $_all_products as $_product ) {
					$option_name = esc_attr( $_product['slug'] ) . '_license_manager';

					settings_errors( $option_name );
				}
				?>
				<div class="wp-filter">
					<ul class="filter-links">
						<li>
							<a href="<?php echo esc_attr( $license_admin_url ); ?>&tab=licenses"
								class="<?php echo esc_attr( ( 'licenses' === $tab || empty( $tab ) ) ? 'current' : '' ); ?>">
								<?php esc_attr_e( 'Licenses', 'slswcclient' ); ?>
							</a>
						</li>
						<li>
							<a href="<?php echo esc_attr( $license_admin_url ); ?>&tab=plugins"
								class="<?php echo ( 'plugins' === $tab ) ? 'current' : ''; ?>">
								<?php esc_attr_e( 'Plugins', 'slswcclient' ); ?>
							</a>
						</li>
						<li>
							<a href="<?php echo esc_attr( $license_admin_url ); ?>&tab=themes"
								class="<?php echo ( 'themes' === $tab ) ? 'current' : ''; ?>">
								<?php esc_attr_e( 'Themes', 'slswcclient' ); ?>
							</a>
						</li>
						<li>
							<a href="<?php echo esc_attr( $license_admin_url ); ?>&tab=api"
								class="<?php echo ( 'api' === $tab ) ? 'current' : ''; ?>">
								<?php esc_attr_e( 'API', 'slswcclient' ); ?>
							</a>
						</li>
					</ul>
				</div>
				<br class="clear" />

				<div class="tablenav-top"></div>
				<?php if ( 'licenses' === $tab || empty( $tab ) ) : ?>
				<div id="licenses">
					<?php self::licenses_form(); ?>
				</div>

				<?php elseif ( 'plugins' === $tab ) : ?>
				<div id="plugins" class="wp-list-table widefat plugin-install">
					<?php self::list_products( self::$plugins ); ?>
				</div>

				<?php elseif ( 'themes' === $tab ) : ?>
				<div id="themes" class="wp-list-table widefat plugin-install">
					<?php self::list_products( self::$themes ); ?>
				</div>

				<?php else : ?>
				<div id="api">
					<?php self::api_form(); ?>
				</div>
					<?php
				endif;
				?>
				<?php
		}

		/**
		 * Output licenses form
		 *
		 * @return  void
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public static function licenses_form() {
			?>
			<style>
			.licenses-table{margin-top: 9px;}
			.licenses-table th, .licenses-table td {padding: 8px 10px;}
			.licenses-table .actions {vertical-align: middle;width: 20px;}
			.licenses-table .license-field input[type="text"], .licenses-table .license-field select{
				width: 100% !important;
			}
			</style>
			<?php

			if ( ! empty( $_POST['licenses'] ) && ! empty( $_POST['save_licenses_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['save_licenses_nonce'] ) ), 'save_licenses' ) ) {
				// phpcs:ignore
				$post_licenses       = isset( $_POST['licenses'] ) ? wp_unslash( $_POST['licenses'] ) : array();

				if ( ! empty( $post_licenses ) ) {
					foreach ( $post_licenses as $slug => $license_details ) {
						$license_details = recursive_parse_args(
							$license_details,
							array(
								'license_status'  => 'inactive',
								'license_key'     => '',
								'license_expires' => '',
								'current_version' => self::$version,
								'environment'     => self::get_environment(),
							)
						);

						do_action( "slswc_save_license_{$slug}", $license_details );
					}
				}
			}

			self::display_messages();
			?>
			<form name="licenses-form" action="" method="post">
				<?php wp_nonce_field( 'save_licenses', 'save_licenses_nonce' ); ?>
				<table class="form-table licenses-table widefat striped" >
					<thead>
						<tr>
							<th><?php esc_attr_e( 'Product Name', 'slswcclient' ); ?></th>
							<th><?php esc_attr_e( 'License Key', 'slswcclient' ); ?></th>
							<th><?php esc_attr_e( 'License Status', 'slswcclient' ); ?></th>
							<th><?php esc_attr_e( 'License Expires', 'slswcclient' ); ?></th>
							<th><?php esc_attr_e( 'Deactivate', 'slswcclient' ); ?></th>
							<th><?php esc_attr_e( 'Environment', 'slswcclient' ); ?></th>
							<?php do_action( 'slswc_after_licenses_column_headings' ); ?>
							<!--<th><?php esc_attr_e( 'Action', 'slswcclient' ); ?></th>-->
						</tr>
					</thead>
					<tbody>
						<?php
						if ( ! empty( self::$plugins ) ) :
							self::licenses_rows( self::$plugins );
							do_action( 'slswc_after_plugins_licenses_list' );
							endif;

						if ( ! empty( self::$themes ) ) :
							self::licenses_rows( self::$themes );
							do_action( 'slswc_after_themes_licenses_list' );
							endif;
						?>
						<?php do_action( 'slswc_after_products_licenses_list', self::$plugins, self::$themes ); ?>
					</tbody>
				</table>
				<p>
					<?php submit_button( __( 'Save Licenses', 'slswcclient' ), 'primary', 'save_licenses' ); ?>
				</p>
			</form>
			<?php
		}

		/**
		 * Licenses rows output
		 *
		 * @param   array $products The list of software products.
		 * @return  void
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public static function licenses_rows( $products ) {

			foreach ( $products as $product ) :
				$slug         = esc_attr( $product['slug'] );
				$option_name  = $slug . '_license_manager';
				$license_info = get_option( $option_name, array() );
				$product_name = ! empty( $product['name'] ) ? $product['name'] : $product['title'];

				$has_license_info    = empty( $license_info ) ? false : true;
				$license_key         = $has_license_info ? trim( $license_info['license_key'] ) : '';
				$current_version     = $has_license_info ? trim( $license_info['current_version'] ) : '';
				$license_status      = $has_license_info ? trim( $license_info['license_status'] ) : '';
				$license_expires     = $has_license_info ? trim( $license_info['license_expires'] ) : '';
				$license_environment = $has_license_info ? trim( $license_info['environment'] ) : self::get_environment();
				$active_status       = $has_license_info ? ( array_key_exists( 'active_status', $license_info ) && 'yes' === $license_info['active_status'][ $license_environment ] ? true : false ) : false;
				?>
				<tr>
					<td><?php echo esc_attr( $product_name ); ?></td>
					<td class="license-field">
						<input type="text"
								name="licenses[<?php echo esc_attr( $slug ); ?>][license_key]"
								id="<?php echo esc_attr( $slug ); ?>_license_key"
								value="<?php echo esc_attr( $license_key ); ?>"
						/>
						<input type="hidden"
								name="licenses[<?php echo esc_attr( $slug ); ?>][current_version]"
								id="<?php echo esc_attr( $slug ); ?>_current_version"
								value="<?php echo esc_attr( $current_version ); ?>"
						/>
					</td>
					<td class="license-field">
						<?php self::license_status_field( $license_status ); ?>
						<input type="hidden"
								name="licenses[<?php echo esc_attr( $slug ); ?>][license_status]"
								id="<?php echo esc_attr( $slug ); ?>_license_status"
								value="<?php echo esc_attr( $license_status ); ?>"
						/>
					</td>
					<td class="license-field">
						<?php echo esc_attr( $license_expires ); ?>
						<input type="hidden"
								name="licenses[<?php echo esc_attr( $slug ); ?>][license_expires]"
								id="<?php echo esc_attr( $slug ); ?>_license_expires"
								value="<?php echo esc_attr( $license_expires ); ?>"
						/>
					</td>
					<td class="license-field">
						<input type="checkbox"
								name="licenses[<?php echo esc_attr( $slug ); ?>][deactivate_license]"
								value="deactivate_license"
								id="<?php echo esc_attr( $slug ); ?>_deactivate_license"
								<?php is_array( $license_info ) && array_key_exists( 'deactivate_license', $license_info ) && ! $active_status ? checked( $license_info['deactivate_license'], 'deactivate_license' ) : ''; ?>
						/>
					</td>
					<td class="license-field">
						<input type="radio"
								name="licenses[<?php echo esc_attr( $slug ); ?>][environment]"
								id="<?php echo esc_attr( $slug ); ?>_environment_live"
								value="live"
								<?php checked( $license_environment, 'live' ); ?>
						/> <?php echo esc_attr( __( 'Live', 'slswcclient' ) ); ?>

						<input type="radio"
								name="licenses[<?php echo esc_attr( $slug ); ?>][environment]"
								id="<?php echo esc_attr( $slug ); ?>_environment_staging"
								value="staging"
								<?php checked( $license_environment, 'staging' ); ?>
						/> <?php echo esc_attr( __( 'Staging', 'slswcclient' ) ); ?>
					</td>
					<?php do_action( 'slswc_after_license_column', $product ); ?>
					<!--<td>
						<a href="#"><span class="dashicons dashicons-yes"></span> Check</a>
					</td>-->
				</tr>
				<?php do_action( 'slswc_after_license_row', $product ); ?>
				<?php
			endforeach;
		}

		/**
		 * Output a list of products.
		 *
		 * @param   string $products The list of products.
		 * @return  void
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public static function list_products( $products ) {
			$products = is_array( $products ) ? $products : (array) $products;

			$type = self::get_tab();

			if ( in_array( $type, array( 'plugins', 'themes' ), true ) ) {
				$slugs    = array();
				$licenses = array();
				foreach ( $products as $slug => $details ) {
					$slugs[]           = $slug;
					$licenses[ $slug ] = $details;
				}
				$args            = array( 'post_name__in' => $slugs );
				$remote_products = (array) self::get_remote_products( $type, $args );
			} else {
				$remote_products = array();
			}

			self::log( 'Local products.' );
			self::log( $products );
			self::log( 'Remote Products' );
			self::log( $remote_products );

			?>
			<?php if ( ! empty( $products ) && count( $products ) > 0 ) : ?>
				<h2 class="screen-reader-text"><?php echo esc_attr( __( 'Plugins List', 'slswcclient' ) ); ?></h2>
				<div id="the-list">
					<?php foreach ( $products as $product ) : ?>
						<?php

						$product = is_array( $product ) ? $product : (array) $product;

						if ( array_key_exists( $product['slug'], $remote_products ) ) {
							$product = recursive_parse_args( (array) $remote_products[ $product['slug'] ], $product );
						}

						$installed = file_exists( $product['file'] ) || is_dir( $product['file'] ) ? true : false;

						$name_version = esc_attr( $product['name'] ) . ' ' . esc_attr( $product['version'] );
						$action_class = $installed ? 'update' : 'install';
						$action_label = $installed ? __( 'Update Now', 'slswcclient' ) : __( 'Install Now', 'slswcclient' );

						do_action( 'slswc_before_products_list', $products );

						$thumb_class = 'theme' === $product['type'] ? 'appearance' : 'plugins';
						?>
					<div class="plugin-card plugin-card-<?php echo esc_attr( $product['slug'] ); ?>">
						<div class="plugin-card-top">
							<div class="name column-name">
								<h3>
									<a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $product['slug'] . '&section=changelog&TB_iframe=true&width=600&height=800' ) ); ?>"
										class="thickbox open-plugin-details-modal">
										<?php echo esc_attr( $product['name'] ); ?>
										<?php if ( '' === $product['thumbnail'] ) : ?>
											<i class="dashicons dashicons-admin-<?php echo esc_attr( $thumb_class ); ?> plugin-icon slswc-product-thumbnail"></i>
										<?php else : ?>
											<img src="<?php echo esc_attr( $product['thumbnail'] ); ?>" class="plugin-icon" alt="<?php echo esc_attr( $name_version ); ?>">
										<?php endif; ?>
									</a>
								</h3>
							</div>
							<div class="action-links">
								<ul class="plugin-action-buttons">
									<li>
										<?php if ( empty( $product['download_url'] ) ) : ?>
											<?php esc_attr_e( 'Manual Download Only.', 'slswcclient' ); ?>
										<?php else : ?>
										<a class="slswc-<?php echo esc_attr( $action_class ); ?>-now <?php echo esc_attr( $action_class ); ?>-now button aria-button-if-js"
											data-download_url="<?php echo esc_url_raw( $product['download_url'] ); ?>"
											data-slug="<?php echo esc_attr( $product['slug'] ); ?>"
											href="#"
											<?php // translators: %s - The license name and version. ?>
											aria-label="<?php echo esc_attr( sprintf( __( 'Update %s now', 'slswcclient' ), esc_attr( $name_version ) ) ); ?>"
											data-name="<?php echo esc_attr( $name_version ); ?>"
											data-nonce="<?php echo esc_attr( wp_create_nonce( 'slswc_client_install_' . $product['slug'] ) ); ?>"
											role="button"
											data-type="<?php echo esc_attr( $product['type'] ); ?>">
											<?php echo esc_attr( $action_label ); ?>
										</a>
										<?php endif; ?>
									</li>
									<li>
										<a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $product['slug'] . '&section=changelog&TB_iframe=true&width=772&height=840' ) ); ?>"
											class="thickbox open-plugin-details-modal"
											<?php // translators: %s - Product name. ?>
											aria-label="<?php echo esc_attr( sprintf( __( 'More information about %s', 'slswcclient' ), esc_attr( $name_version ) ) ); ?>"
											data-title="<?php echo esc_attr( $name_version ); ?>">
											<?php echo esc_attr( __( 'More Details', 'slswcclient' ) ); ?>
										</a>
									</li>
								</ul>
							</div>
							<div class="desc column-description">
								<p><?php echo esc_attr( substr( $product['description'], 0, 110 ) ); ?></p>
								<p class="authors"> <cite>By <a href="<?php echo esc_attr( $product['author_uri'] ); ?>"><?php echo esc_attr( $product['author'] ); ?></a></cite></p>
							</div>
						</div>
						<div class="plugin-card-bottom slswc-plugin-card-bottom">					
							<div class="column-updated">
								<strong>Last Updated: </strong>
								<?php echo esc_attr( human_time_diff( strtotime( $product['updated'] ) ) ); ?> ago.
							</div>
							<div class="column-compatibility">
								<?php self::show_compatible( $product['compatible_to'] ); ?>
							</div>
						</div>
					</div>
					<?php endforeach; ?>
					<?php do_action( 'slswc_after_list_products', $products ); ?>
				</div>
			<?php else : ?>
				<div class="no-products">
					<p><?php esc_attr_e( 'No products in this category yet.', 'slswcclient' ); ?></p>
				</div>
			<?php endif; ?>
			<?php
		}

		/**
		 * Output API Settings form
		 *
		 * @return void
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public static function api_form() {
			$keys = self::get_api_keys();
			?>
			<h2><?php esc_attr_e( 'API Settings', 'slswcclient' ); ?></h2>
			<?php if ( empty( $keys ) && ! self::is_connected() ) : ?>
				<?php
				$username        = isset( $keys['username'] ) ? $keys['username'] : '';
				$consumer_key    = isset( $keys['consumer_key'] ) ? $keys['consumer_key'] : '';
				$consumer_secret = isset( $keys['consumer_secret'] ) ? $keys['consumer_secret'] : '';
				?>
			<p>
				<?php esc_attr_e( 'The API allows you to install plugins directly from the Updates Server into your website instead of downloading and uploading manually.', 'slswcclient' ); ?>
			</p>
			<p class="about-text">
				<?php esc_attr_e( 'Enter API details then save to proceed to the next step to connect', 'slswcclient' ); ?>
			</p>
			<form name="api-keys" method="post" action="">
				<?php wp_nonce_field( 'save_api_keys', 'save_api_keys_nonce' ); ?>
				<input type="hidden" name="save_api_keys_check" value="1" />
				<table class="form-table">
					<tbody>
						<tr>
							<th><?php esc_attr_e( 'Username', 'slswcclient' ); ?></th>
							<td>
								<input type="text"
										name="username"
										value="<?php echo esc_attr( $username ); ?>"
								/>
							</td>
						</tr>
						<tr>
							<th><?php esc_attr_e( 'Consumer Key', 'slswcclient' ); ?></th>
							<td>
								<input type="password"
										name="consumer_key"
										value="<?php echo esc_attr( $consumer_key ); ?>"
								/>
							</td>
						</tr>
						<tr>
							<th><?php esc_attr_e( 'Consumer Secret', '' ); ?></th>
							<td>
								<input type="password"
										name="consumer_secret"
										value="<?php echo esc_attr( $consumer_secret ); ?>"
								/>
							</td>
						</tr>
						<tfoot>
							<tr>
								<th></th>
								<td>
									<input type="submit"
											id="save-api-keys"
											class="button button-primary"
											value="Save API Keys"
									/>
								</td>
							</tr>
						</tfoot>
					</tbody>
				</table>
			</form>
			<?php elseif ( ! empty( $keys ) && ! self::is_connected() ) : ?>
				<form name="connect" method="post" action="">
					<?php wp_nonce_field( 'connect', 'connect_nonce' ); ?>
					<p><?php esc_attr_e( 'Click on the button to connect your account now.', 'slswcclient' ); ?></p>
					<input type="submit"
							id="connect"
							class="button button-primary"
							value="<?php esc_attr_e( 'Connect Account Now', 'slswcclient' ); ?>"
					/>
				</form>

				<form name="reset_api_settings" method="post" action="">
					<?php wp_nonce_field( 'reset_api_settings', 'reset_api_settings_nonce' ); ?>
					<p></p>
					<input type="submit"
							id="reset_api_settings"
							class="button"
							value="<?php esc_attr_e( 'Reset API Keys', 'slswcclient' ); ?>"
					/>
				</form>

			<?php else : ?>
				<p><?php esc_attr_e( 'Your account is connected.', 'slswcclient' ); ?></p>
				<p><?php esc_attr_e( 'You should be able to see a list of your purchased products and get convenient automatic updates.', 'slswcclient' ); ?></p>
				<form name="disconnect" method="post" action="">
					<?php wp_nonce_field( 'disconnect', 'disconnect_nonce' ); ?>
					<input type="submit"
							id="disconnect"
							class="button button-primary"
							value="<?php esc_attr_e( 'Disconnect', 'slswcclient' ); ?>"
					/>
				</form>
			<?php endif; ?>
			<?php
		}

		/**
		 * Output the product ratings
		 *
		 * @return void
		 * @since   1.0.0
		 * @version 1.0.0
		 *
		 * @param array $args The options for the rating.
		 */
		public static function output_ratings( $args ) {
			wp_star_rating( $args );
			?>
			<span class="num-ratings" aria-hidden="true">(<?php echo esc_attr( $args['number'] ); ?>)</span>
			<?php
		}

		/**
		 * Show compatibility message
		 *
		 * @param   string $version - The version to compare with installed WordPress version.
		 * @return  void
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public static function show_compatible( $version ) {
			global $wp_version;
			$compatible = version_compare( $version, $wp_version ) >= 0 ? true : false;

			if ( $compatible ) {
				$compatibility_label = __( 'Compatible', 'slswcclient' );
				$compatibility_class = 'compatible';
			} else {
				$compatibility_label = __( 'Not compatible', 'slswcclient' );
				$compatibility_class = 'incompatible';
			}
			?>
			<span class="compatibility-<?php echo esc_attr( $compatibility_class ); ?>">
				<strong><?php echo esc_attr( $compatibility_label ); ?></strong>
				<?php
				esc_attr_e( ' with your version of WordPress', 'slswcclient' );
				?>
			</span>
			<?php
		}

		/**
		 * License acivated field.
		 *
		 * @since 1.0.0
		 * @since 1.0.1
		 * @version 1.0.0
		 *
		 * @param string $status The license status.
		 */
		public static function license_status_field( $status ) {

			$license_labels = self::license_status_types();

			echo empty( $status ) ? '' : esc_attr( $license_labels[ $status ] );
		}

		/**
		 * The available license status types
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public static function license_status_types() {

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
		}

		/**
		 * Connect to the api server using API keys
		 *
		 * @return  array
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public static function connect() {
			$keys       = self::get_api_keys();
			$connection = self::server_request( 'connect', $keys );

			self::log( 'Connecting...' );

			if ( $connection && $connection->connected && 'ok' === $connection->status ) {
				update_option( 'slswc_api_connected', apply_filters( 'slswc_api_connected', 'yes' ) );
				update_option( 'slswc_api_auth_user', apply_filters( 'slswc_api_auth_user', $connection->auth_user ) );

				return true;
			}

			return false;
		}

		/**
		 * Get more details about the product from the license server.
		 *
		 * @param   string $slug The software slug.
		 * @param   string $type The type of software. Expects plugin/theme, default 'plugin'.
		 * @return  array
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public static function get_remote_product( $slug = '', $type = 'plugin' ) {

			$request_info = array(
				'slug' => empty( $slug ) ? self::$slug : $slug,
				'type' => $type,
			);

			$license_data = get_option( $slug . '_license_manager', null );

			if ( self::is_connected() ) {
				$request_info = array_merge( $request_info, self::get_api_keys() );
			} elseif ( null !== $license_data && ! empty( $license_data['license_key'] ) ) {
				$request_info['license_key'] = trim( $license_data['license_key'] );
			}

			$response = self::server_request( 'product', $request_info );

			if ( is_object( $response ) && 'ok' === $response->status ) {
				return $response->product;
			}

			self::log( 'Get remote product' );
			self::log( $response->product );

			return array();
		}

		/**
		 * Get a user's purchased products.
		 *
		 * @param   string $type The type of products. Expects plugins|themes, default 'plugins'.
		 * @param   array  $args The arguments to form the query to search for the products.
		 * @return  array
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public static function get_remote_products( $type = 'plugins', $args = array() ) {
			$licensed_products = array();
			$request_info      = array();
			$slugs             = array();

			$request_info['type'] = $type;

			$licenses_data = self::get_license_data_for_all( $type );

			foreach ( $licenses_data as $slug => $_license_data ) {
				if ( ! self::ignore_status( $_license_data['license_status'] ) ) {
					$_license_data['domain']    = untrailingslashit( str_ireplace( array( 'http://', 'https://' ), '', home_url() ) );
					$_license_data['slug']      = $slug;
					$slugs[]                    = $slug;
					$licensed_products[ $slug ] = $_license_data;
				}
			}

			if ( ! empty( $licensed_products ) ) {
				$request_info['licensed_products'] = $licensed_products;
			}

			$request_info['query_args'] = wp_parse_args(
				$args,
				array(
					'post_name__in' => $slugs,
				)
			);

			if ( self::is_connected() ) {
				$request_info['api_keys'] = self::get_api_keys();
			}

			$response = self::server_request( 'products', $request_info );

			self::log( 'Getting remote products' );
			self::log( $response );

			if ( is_object( $response ) && 'ok' === $response->status ) {
				return $response->products;
			}

			return array();
		}

		/**
		 * Get license data for all locally installed
		 *
		 * @param   string $type The type of products to return license details for. Expects `plugins` or `themes`, default empty.
		 * @return  array
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function get_license_data_for_all( $type = '' ) {
			$all_products  = array();
			$licenses_data = array();

			if ( self::valid_type( $type ) ) {
				$function              = "get_local_{$type}";
				$all_products[ $type ] = self::$function();
			} else {
				$all_products['themes']  = self::get_local_themes();
				$all_products['plugins'] = self::get_local_plugins();
			}

			foreach ( $all_products as $type => $_products ) {

				foreach ( $_products as $slug => $_product ) {
					$_license_data          = get_option( $slug . '_license_manager', array() );
					$licenses_data[ $slug ] = $_license_data;
				}
			}

			$maybe_type_key = '' !== $type ? $type : '';
			return apply_filters( 'slswc_client_licence_data_for_all' . $maybe_type_key, $licenses_data );
		}

		/**
		 * Check if valid product type.
		 *
		 * @param   string $type The plural product type plugins|themes.
		 * @return  bool
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function valid_type( $type ) {
			return in_array( $type, array( 'themes', 'plugins' ), true );
		}

		/**
		 * Check if status should be ignored
		 *
		 * @param   string $status The status tp check.
		 * @return  bool
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function ignore_status( $status ) {
			$ignored_statuses = array( 'expired', 'max_activations', 'failed' );
			return in_array( $status, $ignored_statuses, true );
		}

		/**
		 * Get the current tab.
		 *
		 * @return  string
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function get_tab() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			return isset( $_GET['tab'] ) && ! empty( $_GET['tab'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) : '';
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Get local themes.
		 *
		 * Get locally installed themes that have SLSWC file headers.
		 *
		 * @return  array $installed_themes List of plugins.
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function get_local_themes() {

			if ( ! function_exists( 'wp_get_themes' ) ) {
				return array();
			}

			$themes = wp_cache_get( 'slswc_themes', 'slswc' );

			if ( empty( $themes ) ) {
				$wp_themes = wp_get_themes();
				$themes    = array();

				foreach ( $wp_themes as $theme_file => $theme_details ) {
					if ( $theme_details->get( 'SLSWC' ) && 'theme' === $theme_details->get( 'SLSWC' ) ) {

						$theme_data = array(
							'file'              => WP_CONTENT_DIR . "/themes/{$theme_file}",
							'name'              => $theme_details->get( 'Name' ),
							'theme_url'         => $theme_details->get( 'ThemeURI' ),
							'description'       => $theme_details->get( 'Description' ),
							'author'            => $theme_details->get( 'Author' ),
							'author_uri'        => $theme_details->get( 'AuthorURI' ),
							'version'           => $theme_details->get( 'Version' ),
							'template'          => $theme_details->get( 'Template' ),
							'status'            => $theme_details->get( 'Status' ),
							'tags'              => $theme_details->get( 'Tags' ),
							'text_domain'       => $theme_details->get( 'TextDomain' ),
							'domain_path'       => $theme_details->get( 'DomainPath' ),
							// SLSWC Headers.
							'slswc'             => ! empty( $theme_details->get( 'SLSWC' ) ) ? $theme_details->get( 'SLSWC' ) : '',
							'slug'              => ! empty( $theme_details->get( 'Slug' ) ) ? $theme_details->get( 'Slug' ) : $theme_details->get( 'TextDomain' ),
							'required_wp'       => ! empty( $theme_details->get( 'RequiredWP' ) ) ? $theme_details->get( 'RequiredWP' ) : '',
							'compatible_to'     => ! empty( $theme_details->get( 'CompatibleTo' ) ) ? $theme_details->get( 'CompatibleTo' ) : '',
							'documentation_url' => ! empty( $theme_details->get( 'DocumentationURL' ) ) ? $theme_details->get( 'DocumentationURL' ) : '',
							'type'              => 'theme',
						);

						$themes[ $theme_details->get( 'Slug' ) ] = wp_parse_args( $theme_data, self::default_remote_product( 'theme' ) );
					}
				}
			}

			wp_cache_add( 'slswc_themes', $themes, 'slswc', apply_filters( 'slswc_themes_cache_expiry', HOUR_IN_SECONDS * 2 ) );

			return $themes;
		}

		/**
		 * Get local plugins.
		 *
		 * Get locally installed plugins that have SLSWC file headers.
		 *
		 * @return  array $installed_plugins List of plugins.
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function get_local_plugins() {

			if ( ! function_exists( 'get_plugins' ) ) {
				return array();
			}

			$plugins = wp_cache_get( 'slswc_plugins', 'slswc' );

			if ( empty( $plugins ) ) {
				$plugins    = array();
				$wp_plugins = get_plugins();

				foreach ( $wp_plugins as $plugin_file => $plugin_details ) {
					if ( isset( $plugin_details['SLSWC'] ) && 'plugin' === $plugin_details['SLSWC'] ) {

						$plugin_data = array(
							'file'              => WP_CONTENT_DIR . "/plugins/{$plugin_file}",
							'name'              => $plugin_details['Name'],
							'title'             => $plugin_details['Title'],
							'description'       => $plugin_details['Description'],
							'author'            => $plugin_details['Author'],
							'author_uri'        => $plugin_details['AuthorURI'],
							'version'           => $plugin_details['Version'],
							'plugin_url'        => $plugin_details['PluginURI'],
							'text_domain'       => $plugin_details['TextDomain'],
							'domain_path'       => $plugin_details['DomainPath'],
							'network'           => $plugin_details['Network'],
							// SLSWC Headers.
							'slswc'             => ! empty( $plugin_details['SLSWC'] ) ? $plugin_details['SLSWC'] : '',
							'slug'              => ! empty( $plugin_details['Slug'] ) ? $plugin_details['Slug'] : $plugin_details['TextDomain'],
							'required_wp'       => ! empty( $plugin_details['RequiredWP'] ) ? $plugin_details['RequiredWP'] : '',
							'compatible_to'     => ! empty( $plugin_details['CompatibleTo'] ) ? $plugin_details['CompatibleTo'] : '',
							'documentation_url' => ! empty( $plugin_details['DocumentationURL'] ) ? $plugin_details['DocumentationURL'] : '',
							'type'              => 'plugin',
						);

						$plugins[ $plugin_details['Slug'] ] = wp_parse_args( $plugin_data, self::default_remote_product( 'theme' ) );
					}
				}

				wp_cache_add( 'slswc_plugins', $plugins, 'slswc', apply_filters( 'slswc_plugins_cache_expiry', HOUR_IN_SECONDS * 2 ) );
			}

			return $plugins;
		}

		/**
		 * Get default remote product data
		 *
		 * @param   string $type The software type. Expects plugin, theme or other. Default plugin.
		 * @return  array $default_data The default product data.
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function default_remote_product( $type = 'plugin' ) {

			$default_data = array(
				'thumbnail'      => '',
				'updated'        => gmdate( 'Y-m-d' ),
				'reviews_count'  => 0,
				'average_rating' => 0,
				'activations'    => 0,
				'type'           => $type,
				'download_url'   => '',
			);

			return $default_data;
		}

		/**
		 * Get the API Keys stored in database
		 *
		 * @return  array
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public static function get_api_keys() {
			return array_filter(
				array(
					'username'        => get_option( 'slswc_api_username', '' ),
					'consumer_key'    => get_option( 'slswc_consumer_key', '' ),
					'consumer_secret' => get_option( 'slswc_consumer_secret', '' ),
				)
			);
		}

		/**
		 * Save a list of products to the database.
		 *
		 * @param array $products List of products to save.
		 * @return void
		 */
		public static function save_products( $products = array() ) {
			if ( empty( $products ) ) {
				$products = self::$products;
			}
			self::log( 'Saving products...' );
			self::log( $products );
			update_option( 'slswc_products', $products );
		}

		/**
		 * Check if the account is connected to the api
		 *
		 * @return  boolean
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public static function is_connected() {
			$is_connected = get_option( 'slswc_api_connected', 'no' );
			return 'yes' === $is_connected ? true : false;
		}

		/**
		 * Recursively merge two arrays.
		 *
		 * @param array $args User defined args.
		 * @param array $defaults Default args.
		 * @return array $new_args The two array merged into one.
		 */
		public static function recursive_parse_args( $args, $defaults ) {
			$args     = (array) $args;
			$new_args = (array) $defaults;
			foreach ( $args as $key => $value ) {
				if ( is_array( $value ) && isset( $new_args[ $key ] ) ) {
					$new_args[ $key ] = recursive_parse_args( $value, $new_args[ $key ] );
				} else {
					$new_args[ $key ] = $value;
				}
			}
			return $new_args;
		}

		/**
		 * ---------------------------------------------------------------------------------
		 * Server Request Functions
		 * ---------------------------------------------------------------------------------
		 */

		/**
		 * Send a request to the server.
		 *
		 * @param   string $action activate|deactivate|check_update.
		 * @param   array  $request_info The data to be sent to the server.
		 * @param   string $domain The domain to send the data to.
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public static function server_request( $action = 'check_update', $request_info = array(), $domain = '' ) {

			$domain = empty( $domain ) ? self::$license_server_url : $domain;

			// Allow filtering the request info for plugins.
			$request_info = apply_filters( 'slswc_request_info_' . self::$slug, $request_info );

			// Build the server url api end point fix url build to support the WordPress API.
			$server_request_url = esc_url_raw( $domain . 'wp-json/slswc/v1/' . $action . '?' . http_build_query( $request_info ) );

			// Options to parse the wp_safe_remote_get() call.
			$request_options = array( 'timeout' => 30 );

			// Allow filtering the request options.
			$request_options = apply_filters( 'slswc_request_options_' . self::$slug, $request_options );

			// Query the license server.
			$endpoint_get_actions = apply_filters( 'slswc_client_get_actions', array( 'product', 'products' ) );
			if ( in_array( $action, $endpoint_get_actions, true ) ) {
				$response = wp_safe_remote_get( $server_request_url, $request_options );
			} else {
				$response = wp_safe_remote_post( $server_request_url, $request_options );
			}

			// Validate that the response is valid not what the response is.
			$result = self::validate_response( $response );

			// Check if there is an error and display it if there is one, otherwise process the response.
			if ( ! is_wp_error( $result ) ) {

				$response_body = json_decode( wp_remote_retrieve_body( $response ) );

				// Check the status of the response.
				$continue = self::check_response_status( $response_body );

				if ( $continue ) {
					return $response_body;
				}
			} else {
				self::log( 'There was an error executing this request, please check the errors below.' );
				// phpcs:disable
				self::log( print_r( $response, true ) );
				// phpcs:enable

				self::add_message(
					self::$slug . '_license_manager',
					$result->get_error_message(),
					'error'
				);

				// Return null to halt the execution.
				return null;
			}
		}

		/**
		 * Validate the license server response to ensure its valid response not what the response is.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @param WP_Error|Array $response The response or WP_Error.
		 */
		public static function validate_response( $response ) {

			if ( ! empty( $response ) ) {

				// Can't talk to the server at all, output the error.
				if ( is_wp_error( $response ) ) {
					return new WP_Error(
						$response->get_error_code(),
						sprintf(
							// translators: 1. Error message.
							__( 'HTTP Error: %s', 'slswcclient' ),
							$response->get_error_message()
						)
					);
				}

				// There was a problem with the initial request.
				if ( ! isset( $response['response']['code'] ) ) {
					return new WP_Error( 'slswc_no_response_code', __( 'wp_safe_remote_get() returned an unexpected result.', 'slswcclient' ) );
				}

				// There is a validation error on the server side, output the problem.
				if ( 400 === $response['response']['code'] ) {

					$body = json_decode( $response['body'] );

					foreach ( $body->data->params as $param => $message ) {
						return new WP_Error(
							'slswc_validation_failed',
							sprintf(
								// translators: %s: Error/response message.
								__( 'There was a problem with your license: %s', 'slswcclient' ),
								$message
							)
						);
					}
				}

				// The server is broken.
				if ( 500 === $response['response']['code'] ) {
					return new WP_Error(
						'slswc_internal_server_error',
						sprintf(
							// translators: %s: the http response code from the server.
							__( 'There was a problem with the license server: HTTP response code is : %s', 'slswcclient' ),
							$response['response']['code']
						)
					);
				}

				if ( 200 !== $response['response']['code'] ) {
					return new WP_Error(
						'slswc_unexpected_response_code',
						sprintf(
							__( 'HTTP response code is : % s, expecting ( 200 )', 'slswcclient' ),
							$response['response']['code']
						)
					);
				}

				if ( empty( $response['body'] ) ) {
					return new WP_Error(
						'slswc_no_response',
						__( 'The server returned no response.', 'slswcclient' )
					);
				}

				return true;
			}
		}

		/**
		 * Validate the license server response to ensure its valid response not what the response is.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @param   object $response_body The data returned.
		 */
		public static function check_response_status( $response_body ) {
			self::log( 'Check response' );
			self::log( $response_body );

			if ( is_object( $response_body ) && ! empty( $response_body ) ) {

				$license_status_types = self::license_status_types();
				$status               = $response_body->status;

				return ( array_key_exists( $status, $license_status_types ) || 'ok' === $status ) ? true : false;
			}

			return false;
		}

		/**
		 * Install a product.
		 *
		 * @param string $slug    Product slug.
		 * @param string $package The product download url.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public static function product_background_installer( $slug = '', $package = '' ) {
			global $wp_filesystem;

			$slug = isset( $_REQUEST['slug'] ) ? wp_unslash( sanitize_text_field( wp_unslash( $_REQUEST['slug'] ) ) ) : '';
			if ( ! array_key_exists( 'nonce', $_REQUEST )
				|| ! empty( $_REQUEST ) && array_key_exists( 'nonce', $_REQUEST )
				&& isset( $_REQUEST ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'slswc_client_install_' . $slug ) ) {
				wp_send_json_error(
					array(
						'message' => esc_attr__( 'Failed to install product. Security token invalid.', 'slswcclient' ),
					)
				);
			}

			$download_link = isset( $_POST['package'] ) ? sanitize_text_field( wp_unslash( $_POST['package'] ) ) : '';
			$name          = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$product_type  = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';

			if ( ! empty( $download_link ) ) {
				// Suppress feedback.
				ob_start();

				try {

					$temp_file = download_url( $download_link, 60 );

					if ( ! is_wp_error( $temp_file ) ) {
						require_once ABSPATH . '/wp-admin/includes/file.php';
						WP_Filesystem();

						if ( 'plugin' === $product_type ) {
							$destination_dir = WP_CONTENT_DIR . '/plugins';
						} elseif ( 'theme' === $product_type ) {
							$destination_dir = WP_CONTENT_DIR . '/themes';
						} else {
							$destination_dir = WP_CONTENT_DIR;
						}

						$temp_dir = WP_CONTENT_DIR . '/slswcclient_temp_downloads';
						if ( ! $wp_filesystem->is_dir( $temp_dir ) ) {
							$wp_filesystem->mkdir( $temp_dir, FS_CHMOD_DIR );
						}

						$file_name   = $slug . '.zip';
						$destination = $temp_dir . $file_name;

						if ( $wp_filesystem->exists( $temp_file ) ) {
							$wp_filesystem->move( $temp_file, $destination, true );
						}

						if ( $wp_filesystem->exists( $destination ) ) {
							$unzipfile = unzip_file( $destination, $destination_dir );

							if ( $unzipfile ) {
								$deleted = $wp_filesystem->delete( $destination );
								wp_send_json_success(
									array(
										'message' => sprintf(
											// translators: %s - the name of the plugin/theme.
											__( 'Successfully installed new version of %s', 'slswcclient' ),
											$name
										),
									)
								);
							} else {
								wp_send_json_error(
									array(
										'slug'    => $slug,
										'message' => __( 'Installation failed. There was an error extracting the downloaded file.', 'slswcclient' ),
									)
								);
							}
						}
					}
				} catch ( Exception $e ) {
					wp_send_json_error(
						array(
							'slug'    => $slug . '_install_error',
							'message' => sprintf(
								// translators: 1: theme slug, 2: error message, 3: URL to install theme manually.
								__( '%1$s could not be installed (%2$s). <a href="%3$s">Please install it manually by clicking here.</a>', 'slswcclient' ),
								$slug,
								$e->getMessage(),
								esc_url( admin_url( 'update.php?action=install-' . $product_type . '&' . $product_type . '=' . $slug . '&_wpnonce=' . wp_create_nonce( 'install-' . $product_type . '_' . $slug ) ) )
							),
						)
					);
				}

				wp_send_json_error( array( 'message' => __( 'No action taken.', 'slswcclient' ) ) );

				// Discard feedback.
				ob_end_clean();
			}

			wp_send_json(
				array(
					'message' => __( 'Failed to install product. Download link not provided or is invalid.', 'slswcclient' ),
				)
			);
		}

		/**
		 * Check if a url qualifies as localhost, staging or development environment.
		 *
		 * @param string $url The url to be checked.
		 * @param string $environment The user specified environment of the url.
		 * @return boolean
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function is_dev( $url = '', $environment = '' ) {
			$is_dev = false;

			if ( 'live' === $environment ) {
				return apply_filters( 'slswc_client_is_dev', false, $url, $environment );
			}

			// Trim the url.
			$url = strtolower( trim( $url ) );

			// Add the scheme so we can use parse_url.
			if ( false === strpos( $url, 'http://' ) && false === strpos( $url, 'https://' ) ) {
				$url = 'http://' . $url;
			}

			$url_parts = wp_parse_url( $url );
			$host      = ! empty( $url_parts['host'] ) ? $url_parts['host'] : false;

			if ( empty( $url ) || ! $host ) {
				return apply_filters( 'slswc_client_is_dev', false );
			}

			$is_ip_local = self::is_ip_local( $host );

			$check_tlds = apply_filters( 'slswc_client_validate_tlds', true );
			$is_tld_dev = false;

			if ( $check_tlds ) {
				$is_tld_dev = self::is_tld_dev( $host );
			}

			$is_subdomain_dev = self::is_subdomain_dev( $host );

			$is_dev = ( $is_ip_local || $is_tld_dev || $is_subdomain_dev ) ? true : false;

			return apply_filters( 'slswc_client_is_dev', $is_dev, $url, $environment );
		}

		/**
		 * Check if a host's IP address is within the local IP range.
		 *
		 * @param string $host The host to be checked.
		 * @return boolean
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function is_ip_local( $host ) {
			if ( 'localhost' === $host ) {
				return true;
			}

			if ( false !== ip2long( $host ) ) {
				if ( ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return true;
				}
			}

			return apply_filters( 'slswc_client_is_ip_local', false, $host );
		}

		/**
		 * Check if a host's TLD is a development or local tld
		 *
		 * @param string $host The host to be checked.
		 * @return boolean
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function is_tld_dev( $host ) {
			$tlds_to_check = apply_filters(
				'slswc_client_url_tlds',
				array(
					'.dev',
					'.local',
					'.test',
				)
			);

			foreach ( $tlds_to_check as $tld ) {
				if ( false !== strpos( $host, $tld ) ) {
					return true;
				}
			}

			return apply_filters( 'slswc_client_is_tld_dev', false, $host );
		}

		/**
		 * Check if a domain contains development subdomain.
		 *
		 * @param string $host The domain to be checked.
		 * @return boolean
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public static function is_subdomain_dev( $host ) {
			if ( substr_count( $host, '.' ) <= 1 ) {
				return false;
			}

			$subdomains_to_check = apply_filters(
				'slswc_client_url_subdomains',
				array(
					'dev.',
					'*.staging.',
					'*.test.',
					'staging-*.',
					'*.wpengine.com',
					'*.easywp.com',
				)
			);

			foreach ( $subdomains_to_check as $subdomain ) {

				$subdomain = str_replace( '.', '(.)', $subdomain );
				$subdomain = str_replace( array( '*', '(.)' ), '(.*)', $subdomain );

				if ( preg_match( '/^(' . $subdomain . ')/', $host ) ) {
					return true;
				}
			}

			return apply_filters( 'slswc_client_is_subdomain_dev', false, $host );
		}

		/**
		 * Get the current environment the client is running on.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @return string
		 */
		public static function get_environment() {
			return self::is_dev( get_option( 'siteurl', '' ) ) ? 'staging' : 'live';
		}

		/**
		 * Class logger so that we can keep our debug and logging information cleaner
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @param mixed $data - The data to go to the error log.
		 */
		public static function log( $data ) {
			$logging_enabled = defined( 'SLSWC_CLIENT_LOGGING' ) && SLSWC_CLIENT_LOGGING ? true : false;
			if ( ! apply_filters( 'slswc_client_logging', $logging_enabled ) ) {
				return;
			}
			//phpcs:disable
			if ( is_array( $data ) || is_object( $data ) ) {
				error_log( __CLASS__ . ' : ' . print_r( $data, true ) );
			} else {
				error_log( __CLASS__ . ' : ' . $data );
			}
			//phpcs:enable

		} // log

		/**
		 * Get all messages to be added to admin notices.
		 *
		 * @return array
		 * @version 1.0.2
		 * @since   1.0.2
		 */
		public static function get_messages() {
			return self::$messages;
		}

		/**
		 * Add a message to be shown to admin
		 *
		 * @param string $key     The array key of the message.
		 * @param string $message The message to be added.
		 * @param string $type    The type of message to be added.
		 * @return void
		 * @version 1.0.2
		 * @since   1.0.2
		 */
		public static function add_message( $key, $message, $type = 'success' ) {
			self::$messages[] = array(
				'key'     => $key,
				'message' => $message,
				'type'    => $type,
			);
		}

		/**
		 * Display license update messages.
		 *
		 * @return void
		 * @version 1.0.2
		 * @since   1.0.2
		 */
		public static function display_messages() {
			if ( empty( self::$messages ) ) {
				return;
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			foreach ( self::$messages as $message ) {
				echo sprintf( '<div class="%1$s notice is-dismissible"><p>%2$s</p></div>', esc_attr( $message['type'] ), wp_kses_post( $message['message'] ) );
			}
		}
	}
endif;

/**
 * Helper functions.
 */

if ( ! function_exists( 'recursive_parse_args' ) ) {
	/**
	 * Recursively merge two arrays.
	 *
	 * @param  array $args User defined args.
	 * @param  array $defaults Default args.
	 * @return array $new_args The two array merged into one.
	 */
	function recursive_parse_args( $args, $defaults ) {
		$args     = (array) $args;
		$new_args = (array) $defaults;
		foreach ( $args as $key => $value ) {
			if ( is_array( $value ) && isset( $new_args[ $key ] ) ) {
				$new_args[ $key ] = recursive_parse_args( $value, $new_args[ $key ] );
			} else {
				$new_args[ $key ] = $value;
			}
		}
		return $new_args;
	}
}

if ( ! function_exists( 'slswc_extra_headers' ) ) {
	/**
	 * Add extra theme headers.
	 *
	 * @param   array $headers The extra theme/plugin headers.
	 * @return  array
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	function slswc_extra_headers( $headers ) {

		if ( ! in_array( 'SLSWC', $headers, true ) ) {
			$headers[] = 'SLSWC';
		}

		if ( ! in_array( 'Updated', $headers, true ) ) {
			$headers[] = 'Updated';
		}

		if ( ! in_array( 'Author', $headers, true ) ) {
			$headers[] = 'Author';
		}

		if ( ! in_array( 'Slug', $headers, true ) ) {
			$headers[] = 'Slug';
		}

		if ( ! in_array( 'Required WP', $headers, true ) ) {
			$headers[] = 'Required WP';
		}

		if ( ! in_array( 'Compatible To', $headers, true ) ) {
			$headers[] = 'Compatible To';
		}

		if ( ! in_array( 'Documentation URL', $headers, true ) ) {
			$headers[] = 'Documentation URL';
		}

		return $headers;
	}
}

/**
 * Fire action hooks and filters.
 */

/**
 * Load the license manager class once all plugins are loaded.
 *
 * @version 1.0.0
 * @since   1.0.0
 */
// Extra Plugin & Theme Header.
add_filter( 'extra_plugin_headers', 'slswc_extra_headers' );
add_filter( 'extra_theme_headers', 'slswc_extra_headers' );

add_action( 'admin_init', 'slswc_client_manager', 12 );
add_action( 'after_setup_theme', 'slswc_client_manager' );
add_action( 'admin_footer', 'slswc_client_admin_script', 11 );

if ( ! function_exists( 'slswc_client_admin_script' ) ) {
	/**
	 * Print admin script for SLSWC Client.
	 *
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function slswc_client_admin_script() {

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
}

if ( ! function_exists( 'slswc_client_manager' ) ) {
	/**
	 * Load the license client manager.
	 *
	 * @return  SLSWC_Client_Manager Instance of the client manager
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function slswc_client_manager() {
		global $slswc_license_server_url, $slswc_slug, $slswc_text_domain;
		return SLSWC_Client_Manager::get_instance( $slswc_license_server_url, $slswc_slug, $slswc_text_domain );
	}
}
