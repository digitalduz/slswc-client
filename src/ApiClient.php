<?php
/**
 * @version     1.0.2
 * @since       1.0.2
 * @package     Client
 * @link        https://licenseserver.io/
 */

namespace Madvault\Slswc\Client;

use Madvault\Slswc\Client\Helper;

/**
 * Class to manage products relying on the Software License Server for WooCommerce.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
//phpcs:ignore
class ApiClient {

	/**
	 * The plugin updater client
	 *
	 * @var Madvalut\Slswc\Client\Client
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $client;

	/**
	 * The url of the server where updates are requested from.
	 *
	 * @var string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $url;

	/**
	 * The slug of the product using the client
	 *
	 * @var [type]
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $slug;

	public function __construct( $client ) {
		$this->client = $client;

		$this->url = $this->client->license_server_url;
		$this->slug = $this->client->slug;
	}

	/**
	 * Connect to the api server using API keys
	 *
	 * @return  boolean
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function connect() {
		$keys       = $this->get_api_keys();
		$connection = helper::server_request( $this->url, 'connect', $keys );

		Helper::log( 'Connecting...' );

		if ( $connection && $connection->connected && 'ok' === $connection->status ) {
			update_option( 'slswc_api_connected_' . esc_attr( $this->slug ), apply_filters( 'slswc_api_connected_' . esc_attr( $this->slug ), 'yes' ) );
			update_option( 'slswc_api_auth_user_' . esc_attr( $this->slug ), apply_filters( 'slswc_api_auth_user_' . esc_attr( $this->slug ), $connection->auth_user ) );

			return true;
		}

		return false;
	}

	/**
	 * Get API Keys
	 *
	 * @return array
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function get_api_keys() {
		return array_filter(
			array(
				'username'        => get_option( 'slswc_api_username_' . esc_attr( $this->slug ), '' ),
				'consumer_key'    => get_option( 'slswc_consumer_key_' . esc_attr( $this->slug ), '' ),
				'consumer_secret' => get_option( 'slswc_consumer_secret_' . esc_attr( $this->slug ), '' ),
			)
		);
	}

	/**
	 * Save API Keys
	 *
	 * @param string $username The API username.
	 * @param string $consumer_key The consumer key.
	 * @param string $consumer_secret The consumer secret.
	 * @return boolean
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function save_api_keys($username, $consumer_key, $consumer_secret) {
		$keys = array(
			'username'        => $username,
			'consumer_key'    => $consumer_key,
			'consumer_secret' => $consumer_secret,
		);
		return update_option(
			'slswc_api_keys_' . esc_attr( $this->slug ),
			$keys
		);
	}

}
