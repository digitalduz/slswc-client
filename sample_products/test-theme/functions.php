<?php
/**
 * Software License Server for WooCommerce
 *
 * @package Test_Theme
 * @author  License Server
 * @version 1.0.0
 * @since   1.0.0
 */

/**
 * Initialize License CLient
 *
 * @return SLSWC_Client
 */
function theme_slswc_client() {
	require_once dirname( __FILE__ ) . '/class-slswc-client.php';
	return SLSWC_Client::get_instance( 'http://example.com', WP_CONTENT_DIR . '/themes/test-theme', 'theme' );
}

add_action( 'wp_loaded', 'theme_slswc_client', 11 );
