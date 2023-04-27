<?php
/**
 * Defines the helper class for client
 *
 * @version     1.0.2
 * @since       1.0.2
 * @package     Client
 * @link        https://licenseserver.io/
 */

namespace Madvault\Slswc\Client;

use \WP_Error;

/**
 * Class responsible for registering updates.
 *
 * @version 1.1.0
 * @since   1.1.0
 */
class Updater {
	/**
	 * The plugin file
	 *
	 * @var string
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public $file;

	/**
	 * The plugin version
	 *
	 * @var string
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public $version;

	/**
	 * Holds a list of plugins
	 *
	 * @var array
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public $plugins = [];

	/**
	 * Holds a list of themes
	 *
	 * @var array
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $themes = [];

	/** */
	public function __construct( $file, $version) {
		$this->file    = $file;
		$this->version = $version;
		$this->init_hooks();
	}

	/**
	 * Initialize core actions and filters
	 *
	 * @return void
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public function init_hooks() {
		add_action( 'init', array( $this, 'init_products' ), 1 );
		register_activation_hook( $this->file, array( $this, 'activate' ) );
		add_filter( 'extra_plugin_headers', array( $this, 'extra_headers' ) );
		add_filter( 'extra_theme_headers', array( $this, 'extra_headers' ) );		
		add_filter( 'site_transient_update_plugins', array( $this, 'change_update_information' ) );
	}

	/**
	 * Initialize products
	 *
	 * @return void
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public function init_products() {
		$this->get_local_plugins();
		$this->get_local_themes();

		$this->add_unlicensed_products_notices();
	}

	/**
	 * Get local themes.
	 *
	 * Get locally installed themes that have SLSWC file headers.
	 *
	 * @return  array $installed_themes List of plugins.
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public function get_local_themes() {

		if ( ! function_exists( 'wp_get_themes' ) ) {
			return array();
		}

		$themes = wp_cache_get( 'slswc_themes', 'slswc' );

		if ( $themes == false ) {
			$wp_themes = wp_get_themes();
			$themes    = array();

			foreach ( $wp_themes as $theme_file => $theme_details ) {
				if ( $theme_details->get( 'SLSWC' ) && 'theme' === $theme_details->get( 'SLSWC' ) ) {
					$theme_data = Helper::format_theme_data( $theme_details, $theme_file );

					$themes[ $theme_details->get( 'Slug' ) ] = wp_parse_args( $theme_data, $this->default_remote_product( 'theme' ) );
				}
			}
		}

		$this->set_themes( $themes );

		wp_cache_add( 'slswc_themes', $themes, 'slswc', apply_filters( 'slswc_themes_cache_expiry', HOUR_IN_SECONDS * 2 ) );

		return $themes;
	}

	/**
	 * Get local plugins.
	 *
	 * Get locally installed plugins that have SLSWC file headers.
	 *
	 * @return  array $installed_plugins List of plugins.
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public function get_local_plugins() {

		if ( ! function_exists( 'get_plugins' ) ) {
			return array();
		}

		$plugins = wp_cache_get( 'slswc_plugins', 'slswc' );

		if ( $plugins == false  ) {
			$plugins    = array();
			$wp_plugins = get_plugins();

			foreach ( $wp_plugins as $plugin_file => $plugin_details ) {
				if ( isset( $plugin_details['SLSWC'] ) && 'plugin' === $plugin_details['SLSWC'] ) {

					$plugin_data = Helper::format_plugin_data( $plugin_details, $plugin_file, 'plugin' );

					$plugins[ $plugin_data['slug'] ] = wp_parse_args( $plugin_data, $this->default_remote_product( 'theme' ) );
				}
			}

			wp_cache_add( 'slswc_plugins', $plugins, 'slswc', apply_filters( 'slswc_plugins_cache_expiry', HOUR_IN_SECONDS * 2 ) );
		}

		$this->set_plugins( $plugins );

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
	public function default_remote_product( $type = 'plugin' ) {

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
	 * Activate the updater
	 *
	 * @return void
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public function activate() {
		update_option( 'slswc_update_client_version', $this->version );		
	}

	/**
	 * Add product to list of plugins
	 *
	 * @return void
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public function add_product() {

	}

	/**
	 * Change update information
	 *
	 * @param object $transient
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function change_update_information ( $transient ) {
		//If we are on the update core page, change the update message for unlicensed products
		global $pagenow;
		if ( ( 'update-core.php' == $pagenow ) && $transient && isset( $transient->response ) && ! isset( $_GET['action'] ) ) {
			$plugins = $this->get_plugins();

			if( empty( $plugins ) ) return $transient;

			$notice_text = __( 'To enable this update please activate your license in Settings > License Manager page.' , 'slswcclient' );

			foreach ( $plugins as $key => $value ) {
				if( isset( $transient->response[ $value->file ] ) && isset( $transient->response[ $value->file ]->package ) && '' == $transient->response[ $value->file ]->package && ( FALSE === stristr($transient->response[ $value->file ]->upgrade_notice, $notice_text ) ) ){
					$message = '<div class="slswcclient-plugin-upgrade-notice">' . $notice_text . '</div>';
					$transient->response[ $value->file ]->upgrade_notice = wp_kses_post( $message );
				}
			}
		}

		return $transient;
	}

	/**
	 * Add action for queued products to display message for unlicensed products.
	 *
	 * @return void
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public function add_unlicensed_products_notices () {
		$plugins = $this->get_plugins();
		if( !is_array( $plugins ) || count( $plugins ) < 0 ) return;

		error_log( print_r( $plugins, true ) );

		foreach ( $plugins as $key => $update ) {
			add_action( 'in_plugin_update_message-' . $update->file, array( $this, 'need_license_message' ), 10, 2 );
		}
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
		if ( empty( $update->package ) ) {
			echo wp_kses_post( '<div class="slswcclient-plugin-upgrade-notice">' . __( 'To enable this update please connect your WooCommerce subscription by visiting the Settings > License Manager page.', 'slswcclient' ) . '</div>' );
		}
	}

	/**
	 * Add extra theme headers.
	 *
	 * @param   array $headers The extra theme/plugin headers.
	 * @return  array
	 * @since   1.1.0
	 * @version 1.1.0
	 */
	public function extra_headers( $headers ) {

		if ( ! in_array( 'SLSWC', $headers, true ) ) {
			$headers[] = 'SLSWC';
		}

		if ( ! in_array( 'SLSWC Updated', $headers, true ) ) {
			$headers[] = 'SLSWC Updated';
		}

		if ( ! in_array( 'Author', $headers, true ) ) {
			$headers[] = 'Author';
		}

		if ( ! in_array( 'SLSWC Slug', $headers, true ) ) {
			$headers[] = 'SLSWC Slug';
		}

		if ( ! in_array( 'Requires at least', $headers, true ) ) {
			$headers[] = 'Requires at least';
		}

		if ( ! in_array( 'SLSWC Compatible To', $headers, true ) ) {
			$headers[] = 'SLSWC Compatible To';
		}

		if ( ! in_array( 'SLSWC Documentation URL', $headers, true ) ) {
			$headers[] = 'SLSWC Documentation URL';
		}

		return $headers;
	}

	/**
	 * Get the plugin file
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 * @return string
	 */
	public function get_file() {
		return $this->file;
	}

	/**
	 * Set the plugin file
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 * @return object
	 */
	public function set_file( $file ) {
		$this->file = $file;

		return $this;
	}

	/**
	 * Get the plugin version
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Set the plugin version
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 * @return object
	 */
	public function set_version( $version ) {
		$this->version = $version;

		return $this;
	}

	/**
	 * Get a list of plugins
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 * @return array
	 */
	public function get_plugins() {
		return $this->plugins;
	}

	/**
	 * Set a list of all plugins
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 * @return object
	 */
	public function set_plugins( $plugins ) {
		$this->plugins = $plugins;

		return $this;
	}

	/**
	 * Get a list of all themes
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 * @return array
	 */
	public function get_themes() {
		return $this->themes;
	}

	/**
	 * Set a list of themes
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 * @return object
	 */
	public function set_themes( $themes ) {
		$this->themes = $themes;

		return $this;
	}
}