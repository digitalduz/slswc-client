<?php
/**
 * Logger class
 *
 * @version     1.0.0
 * @since       1.0.0
 * @package     SLSWC_Client
 * @link        https:// licenseserver.io/
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
class Logger {
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

	}
}
