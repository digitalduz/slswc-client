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

/**
 * Theme update class
 *
 * @version 1.0.0
 * @since   1.0.0
 */
class Theme {
	/**
	 * Client object
	 *
	 * @var Theme
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $client;

	/**
	 * The license details.
	 *
	 * @var LicenseDetails
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $license;

	/**
	 * The plugin slug.
	 *
	 * @var string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $slug;

	/**
	 * The theme version
	 *
	 * @var string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $version;

	/**
	 * The plugin base file name.
	 *
	 * @var string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $plugin_file;

	/**
	 * Initialize the class actions.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @param   string $license_server_url - The base url to your WooCommerce shop.
	 * @param   string $plugin_file - path to the plugin file or directory, relative to the plugins directory.
	 * @param   string $software_type - the type of software this is. plugin|theme, default: plugin.
	 * @param   array  $args - array of additional arguments to override default ones.
	 */
	public function __construct( $license_server_url, $plugin_file, ...$args ) {
		$this->plugin_file = $plugin_file;

		$args = Helper::get_file_details( $this->plugin_file );

		$this->slug = $args['slug'];

		$this->client = ApiClient::get_instance( $license_server_url, $this->slug );

		$this->license = new LicenseDetails(
			$license_server_url,
			$plugin_file
		);
	}

	/**
	 * Initialize the hooks
	 *
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function init_hooks() {
		add_filter( 'pre_set_site_transient_update_themes', array( $this, 'theme_update_check' ), 21, 1 );
		add_filter( 'extra_theme_headers', array( $this, 'extra_headers' ) );
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

		$server_response = $this->client->request( 'check_update' );

		if ( $this->license->check_license( $server_response ) ) {

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
	 * Extra theme headers.
	 *
	 * @param array $headers The array of headers.
	 * @return array
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public function extra_headers( $headers ) {
		return Helper::extra_headers( $headers );
	}
}