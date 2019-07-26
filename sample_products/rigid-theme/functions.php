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
	require_once dirname( __FILE__ ) . '/class-wc-software-license-client.php';
	return WC_Software_License_Client::get_instance( 'http://example.com', WP_CONTENT_DIR . '/themes/rigid-theme', 'theme' );
}

add_action( 'wp_loaded', 'theme_slswc_client', 11 );
