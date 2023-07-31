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

		//$this->add_unlicensed_products_notices();
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
				if ( ! $theme_details->get( 'SLSWC' ) || 'theme' !== $theme_details->get( 'SLSWC' ) ) {
					continue;
				}
				$theme_data = Helper::format_theme_data( $theme_details, $theme_file );
				$themes[ $theme_details->get( 'Slug' ) ] = wp_parse_args( $theme_data, $this->default_remote_product( 'theme' ) );
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

		if ( $plugins === false  ) {
			$plugins    = array();
			$wp_plugins = get_plugins();

			foreach ( $wp_plugins as $plugin_file => $plugin_details ) {
				if ( ! isset( $plugin_details['SLSWC'] ) || 'plugin' !== $plugin_details['SLSWC'] ) {
					continue;
				}

				$plugin_data = Helper::format_plugin_data( $plugin_details, $plugin_file, 'plugin' );
				$plugins[ $plugin_data['slug'] ] = wp_parse_args( $plugin_data, $this->default_remote_product( 'theme' ) );
			}

			wp_cache_add(
				'slswc_plugins',
				$plugins,
				'slswc',
				apply_filters( 'slswc_plugins_cache_expiry', HOUR_IN_SECONDS * 2 )
			);
		}

		$this->set_plugins( $plugins );

		$this->save_products( $plugins, 'slswc_plugins' );

		return $plugins;
	}

	/**
	 * Save products to transient
	 *
	 * @param array $products The products to save.
	 * @param string $key The transient key.
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function save_products( $products, $key = 'slswc_products' ) {
		$slswc_products = array_filter( $products );
		return set_transient( $key, $slswc_products, HOUR_IN_SECONDS );
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