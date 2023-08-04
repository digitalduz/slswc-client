<?php
/**
 * Defines the abstract software updater class
 *
 * @version     1.0.2
 * @since       1.0.2
 * @package     Client
 * @link        https://licenseserver.io/
 */

namespace Madvault\Slswc\Client;

/**
 * Abstract software updater class
 *
 * @version 1.0.0
 * @since   1.0.0
 */
class GenericSoftwareUpdater {
	/**
	 * The instance of this class.
	 *
	 * @var plugin
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public static $instance = null;

	/**
	 * The instance of the ApiClient class.
	 *
	 * @var ApiClient
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $client;

	/**
	 * The license details class.
	 *
	 * @var LicenseDetails
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $license;

	/**
	 * License details
	 *
	 * @var array
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $license_details = array();

	/**
	 * The license server url.
	 *
	 * @var [type]
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $license_server_url;

	/**
	 * Get an instance of this class..
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @param   string $license_server_url - The base url to your WooCommerce shop.
	 * @param   string $base_file          - path to the plugin file or directory, relative to the plugins directory.
	 * @param   array  $args               - array of additional arguments to override default ones.
	 */
	public function __construct( $license_server_url, $base_file, $args ) {
		$this->client  = ApiClient::get_instance(
			$this->license_server_url,
			$this->get_slug()
		);

		$this->license = new LicenseDetails(
			$license_server_url,
			$base_file,
			$this->get_license_details()
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
		return $this->license_details['version'];
	}

	/**
	 * Get the slug.
	 *
	 * @return string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function get_slug() {
		return $this->license_details['slug'];
	}

	/**
	 * Get domain.
	 *
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function get_domain() {
		return $this->license_details['domain'];
	}

	public function get_license_details( ) {
		return $this->license_details;
	}

	/**
	 * Setters
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
		$this->license_details['slug'] = $slug;
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
		$this->license_details['version'] = $version;
	}

	/**
	 * Set the domain
	 *
	 * @param string $domain Set the domain.
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function set_domain( $domain ) {
		$this->license_details['domain'] = $domain;
	}

	/**
	 * Set the license details.
	 *
	 * @param array $license_details The license details.
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function set_license_details( $license_details ) {
		$this->license_details = $license_details;
	}
}

