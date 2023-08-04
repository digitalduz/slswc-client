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

	

	/** */
	public function __construct( $file, $version) {
		$this->file    = $file;
		$this->version = $version;
		
	}

	/**
	 * Initialize core actions and filters
	 *
	 * @return void
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public function init_hooks() {
				
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

	
}