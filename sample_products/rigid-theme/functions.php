<?php
/**
 * Software License Server for WooCommerce
 *
 * @package Test_Theme
 * @author  Red Panda Ventures
 * @version 1.0.0
 * @since   1.0.0
 */

/**
 * Initialize License CLient
 *
 * @return WC_Software_License_Client
 */
function theme_slswc_client() {
	require_once '../../class-wc-software-license-client.php';
	return WC_Software_License_Client::get_instance( 'http://slswc.lindeni.co.za', WP_CONTENT_DIR . '/themes/rigid-theme', 'theme' );
}

theme_slswc_client();
