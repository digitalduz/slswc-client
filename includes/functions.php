<?php
/**
 * License manager class.
 *
 * @version     1.0.0
 * @since       1.0.0
 * @package     SLSWC_Client
 * @link        https:// licenseserver.io/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Helper functions.
 */
if ( ! function_exists( 'recursive_parse_args' ) ) {
	/**
	 * Recursively merge two arrays.
	 *
	 * @param  array $args User defined args.
	 * @param  array $defaults Default args.
	 * @return array $new_args The two array merged into one.
	 */
	function recursive_parse_args( $args, $defaults ) {
		$args     = (array) $args;
		$new_args = (array) $defaults;
		foreach ( $args as $key => $value ) {
			if ( is_array( $value ) && isset( $new_args[ $key ] ) ) {
				$new_args[ $key ] = recursive_parse_args( $value, $new_args[ $key ] );
			} else {
				$new_args[ $key ] = $value;
			}
		}
		return $new_args;
	}
}

if ( ! function_exists( 'slswc_extra_headers' ) ) {
	/**
	 * Add extra theme headers.
	 *
	 * @param   array $headers The extra theme/plugin headers.
	 * @return  array
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	function slswc_extra_headers( $headers ) {

		if ( ! in_array( 'SLSWC', $headers, true ) ) {
			$headers[] = 'SLSWC';
		}

		if ( ! in_array( 'Updated', $headers, true ) ) {
			$headers[] = 'Updated';
		}

		if ( ! in_array( 'Author', $headers, true ) ) {
			$headers[] = 'Author';
		}

		if ( ! in_array( 'Slug', $headers, true ) ) {
			$headers[] = 'Slug';
		}

		if ( ! in_array( 'Required WP', $headers, true ) ) {
			$headers[] = 'Required WP';
		}

		if ( ! in_array( 'Compatible To', $headers, true ) ) {
			$headers[] = 'Compatible To';
		}

		if ( ! in_array( 'Documentation URL', $headers, true ) ) {
			$headers[] = 'Documentation URL';
		}

		return $headers;
	}
}
