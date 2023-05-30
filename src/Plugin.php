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

use Madvault\Slswc\Client\Client;

class Plugin {
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
	 * The Client object.
	 *
	 * @var Client
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	private $client;
	/**
	 * Initialize the class actions.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @param   string $license_server_url - The base url to your WooCommerce shop.
	 * @param   string $base_file - path to the plugin file or directory, relative to the plugins directory.
	 * @param   array  $args - array of additional arguments to override default ones.
	 */
	private function __construct( $license_server_url, $base_file, ...$args ) {
		$this->client = Client::get_instance( $license_server_url, $base_file, 'plugin', ...$args );
	}

	public function init_hooks() {
		$this->client->init_hooks();

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_check' ) );
		add_filter( 'plugins_api', array( $this, 'add_plugin_info' ), 10, 3 );
		add_filter( 'plugin_row_meta', array( $this, 'check_for_update_link' ), 10, 2 );
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

		$response = $this->client->server_request( 'check_update' );

		if ( $this->client->check_license( $response ) ) {
			if ( isset( $response ) && is_object( $response->software_details ) ) {

				$plugin_update_info = $response->software_details;

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
}