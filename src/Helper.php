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
use \Exception;

/**
 * Helper class with static helper methods
 *
 * @version 1.1.0
 * @since   1.1.0
 */
class Helper {
	/**
	 * Check if the account is connected to the api
	 *
	 * @return  boolean
	 * @since   1.1.0
	 * @version 1.1.0
	 */
	public static function is_connected() {
		$is_connected = get_option( 'slswc_api_connected', 'no' );
		return 'yes' === $is_connected ? true : false;
	}

	/**
	 * Get the API Keys stored in database
	 *
	 * @return  array
	 * @since   1.1.0 - Moved from client manager class
	 * @version 1.1.0
	 */
	public static function get_api_keys() {
		return array_filter(
			array(
				'username'        => get_option( 'slswc_api_username', '' ),
				'consumer_key'    => get_option( 'slswc_consumer_key', '' ),
				'consumer_secret' => get_option( 'slswc_consumer_secret', '' ),
			)
		);
	}

	/**
	 * Send a request to the server.
	 *
	 * @param   string $domain The domain to send the data to.
	 * @param   string $action activate|deactivate|check_update.
	 * @param   array  $request_info The data to be sent to the server.
	 
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public static function server_request( $domain, $action = 'check_update', $request_info = array() ) {

		$slug = isset( $request_info['slug'] ) ? $request_info['slug'] : '';

		// Allow filtering the request info for plugins.
		$request_info = apply_filters( 'slswc_request_info_' . $slug, $request_info );

		// Build the server url api end point fix url build to support the WordPress API.
		$server_request_url = esc_url_raw( $domain . 'wp-json/slswc/v1/' . $action . '?' . http_build_query( $request_info ) );

		// Options to parse the wp_safe_remote_get() call.
		$request_options = array( 'timeout' => 30 );

		// Allow filtering the request options.
		$request_options = apply_filters( 'slswc_request_options_' . $slug, $request_options );

		// Query the license server.
		$endpoint_get_actions = apply_filters( 'slswc_client_get_actions', array( 'product', 'products' ) );
		if ( in_array( $action, $endpoint_get_actions, true ) ) {
			$response = wp_safe_remote_get( $server_request_url, $request_options );
		} else {
			$response = wp_safe_remote_post( $server_request_url, $request_options );
		}

		// Validate that the response is valid not what the response is.
		$result = self::validate_response( $response );

		// Check if there is an error and display it if there is one, otherwise process the response.
		if ( ! is_wp_error( $result ) ) {

			$response_body = json_decode( wp_remote_retrieve_body( $response ) );

			// Check the status of the response.
			$continue = self::check_response_status( $response_body );

			if ( $continue ) {
				return $response_body;
			}
		} else {
			Helper::log( 'There was an error executing this request, please check the errors below.' );
			// phpcs:disable
			Helper::log( print_r( $response, true ) );
			// phpcs:enable

			// Return null to halt the execution.
			return array(
				'status' => $response['response']['code'],
				'response' => $result->get_error_message(),
			);
		}
	}

	/**
	 * Get theme or plugin information from file.
	 *
	 * @param   string $base_file - Plugin file or theme slug.
	 * @param   string $type - Product type. plugin|theme.
	 * @return  array
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public static function get_file_information( $base_file, $type = 'plugin' ) {
		$data = array();
		if ( 'plugin' === $type ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$plugin = get_plugin_data( $base_file, false );

			$data = self::format_plugin_data( $plugin, $base_file, $type );
		} elseif ( 'theme' === $type ) {
			if ( ! function_exists( 'wp_get_theme' ) ) {
				require_once ABSPATH . 'wp-includes/theme.php';
			}
			$theme = wp_get_theme( basename( $base_file ) );

			$data = self::format_theme_data( $theme, $base_file);
		}

		return $data;

	}

	/**
	 * Format plugin data
	 *
	 * @param array  $data The data to format.
	 * @param string $file The plugin file.
	 * @param string $type The product type.
	 * @return void
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public static function format_plugin_data( $data, $file = '', $type = 'plugin' ) {
		$formatted_data = array(				
			'name'              => $data['Name'],
			'title'             => $data['Title'],
			'description'       => $data['Description'],
			'author'            => $data['Author'],
			'author_uri'        => $data['AuthorURI'],
			'version'           => $data['Version'],
			'plugin_url'        => $data['PluginURI'],
			'text_domain'       => $data['TextDomain'],
			'domain_path'       => $data['DomainPath'],
			'network'           => $data['Network'],
			// SLSWC Headers.
			'slswc'             => ! empty( $data['SLSWC'] ) ? $data['SLSWC'] : '',
			'slug'              => ! empty( $data['SLSWCSlug'] ) ? $data['Slug'] : $data['TextDomain'],
			'requires_wp'       => ! empty( $data['RequiresWP'] ) ? $data['RequiresWP'] : '',
			'compatible_to'     => ! empty( $data['SLSWCCompatibleTo'] ) ? $data['SLSWCCompatibleTo'] : '',
			'documentation_url' => ! empty( $data['SLSWCDocumentationURL'] ) ? $data['SLSWCDocumentationURL'] : '',
			'type'              => $type,
		);

		if ( $file != '' ) {
			$sub_dir = $type === 'theme' ? 'themes' : 'plugins';
			$formatted_data['file'] = WP_CONTENT_DIR . "/{$sub_dir}/{$file}";
		}

		return apply_filters( 'slswc_client_formatted_plugin_data', $formatted_data, $data, $file, $type );
	}

	/**
	 * Format theme data
	 *
	 * @param object $theme The theme object.
	 * @param string $theme_file The theme file.
	 * @return void
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	public static function format_theme_data ( $theme, $theme_file ) {
		$formatted_data = array(
			'file'              => WP_CONTENT_DIR . "/themes/{$theme_file}",
			'name'              => $theme->get( 'Name' ),
			'theme_url'         => $theme->get( 'ThemeURI' ),
			'description'       => $theme->get( 'Description' ),
			'author'            => $theme->get( 'Author' ),
			'author_uri'        => $theme->get( 'AuthorURI' ),
			'version'           => $theme->get( 'Version' ),
			'template'          => $theme->get( 'Template' ),
			'status'            => $theme->get( 'Status' ),
			'tags'              => $theme->get( 'Tags' ),
			'text_domain'       => $theme->get( 'TextDomain' ),
			'domain_path'       => $theme->get( 'DomainPath' ),
			// SLSWC Headers.
			'slswc'             => ! empty( $theme->get( 'SLSWC' ) ) ? $theme->get( 'SLSWC' ) : '',
			'slug'              => ! empty( $theme->get( 'SLSWCSlug' ) ) ? $theme->get( 'SLSWCSlug' ) : $theme->get( 'TextDomain' ),
			'requires_wp'       => ! empty( $theme->get( 'RequiresWP' ) ) ? $theme->get( 'RequiresWP' ) : '',
			'compatible_to'     => ! empty( $theme->get( 'SLSWCCompatibleTo' ) ) ? $theme->get( 'SLSWCCompatibleTo' ) : '',
			'documentation_url' => ! empty( $theme->get( 'SLSWCDocumentationURL' ) ) ? $theme->get( 'SLSWCDocumentationURL' ) : '',
			'type'              => 'theme',
		);

		return apply_filters( 'slswc_client_formatted_theme_data', $formatted_data, $theme, $theme_file );
	}

	/**
	 * Recursively merge two arrays.
	 *
	 * @param  array $args User defined args.
	 * @param  array $defaults Default args.
	 * @return array $new_args The two array merged into one.
	 */
	public static function recursive_parse_args( $args, $defaults ) {
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

	/**
	 * Validate the license server response to ensure its valid response not what the response is.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @param WP_Error|Array $response The response or WP_Error.
	 */
	public static function validate_response( $response ) {

		if ( ! empty( $response ) ) {

			// Can't talk to the server at all, output the error.
			if ( is_wp_error( $response ) ) {
				return new WP_Error(
					$response->get_error_code(),
					sprintf(
						// translators: 1. Error message.
						__( 'HTTP Error: %s', 'slswcclient' ),
						$response->get_error_message()
					)
				);
			}

			// There was a problem with the initial request.
			if ( ! isset( $response['response']['code'] ) ) {
				return new WP_Error( 'slswc_no_response_code', __( 'wp_safe_remote_get() returned an unexpected result.', 'slswcclient' ) );
			}

			// There is a validation error on the server side, output the problem.
			if ( 400 === $response['response']['code'] ) {

				$body = json_decode( $response['body'] );

				$response_message = '';

				foreach ( $body->data->params as $param => $message ) {
					$response_message .= $message;
				}

				return new WP_Error(
					'slswc_validation_failed',
					sprintf(
						// translators: %s: Error/response message.
						__( 'There was a problem with your license: %s', 'slswcclient' ),
						$response_message
					)
				);
			}

			// The server is broken.
			if ( 500 === $response['response']['code'] ) {
				return new WP_Error(
					'slswc_internal_server_error',
					sprintf(
						// translators: %s: the http response code from the server.
						__( 'There was a problem with the license server: HTTP response code is : %s', 'slswcclient' ),
						$response['response']['code']
					)
				);
			}

			if ( 200 !== $response['response']['code'] ) {
				return new WP_Error(
					'slswc_unexpected_response_code',
					sprintf(
						__( 'HTTP response code is : % s, expecting ( 200 )', 'slswcclient' ),
						$response['response']['code']
					)
				);
			}

			if ( empty( $response['body'] ) ) {
				return new WP_Error(
					'slswc_no_response',
					__( 'The server returned no response.', 'slswcclient' )
				);
			}

			return true;
		}
	}

	/**
	 * Validate the license server response to ensure its valid response not what the response is.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @param   object $response_body The data returned.
	 */
	public static function check_response_status( $response_body ) {
		Helper::log( 'Check response' );
		Helper::log( $response_body );

		if ( is_object( $response_body ) && ! empty( $response_body ) ) {

			$license_status_types = self::license_status_types();
			$status               = $response_body->status;

			return ( array_key_exists( $status, $license_status_types ) || 'ok' === $status ) ? true : false;
		}

		return false;
	}

	/**
	 * Install a product.
	 *
	 * @param string $slug    Product slug.
	 * @param string $package The product download url.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public static function product_background_installer( $slug = '', $package = '' ) {
		global $wp_filesystem;

		$slug = isset( $_REQUEST['slug'] ) ? wp_unslash( sanitize_text_field( wp_unslash( $_REQUEST['slug'] ) ) ) : '';
		if ( ! array_key_exists( 'nonce', $_REQUEST )
			|| ! empty( $_REQUEST ) && array_key_exists( 'nonce', $_REQUEST )
			&& isset( $_REQUEST ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'slswc_client_install_' . $slug ) ) {
			wp_send_json_error(
				array(
					'message' => esc_attr__( 'Failed to install product. Security token invalid.', 'slswcclient' ),
				)
			);
		}

		$download_link = isset( $_POST['package'] ) ? sanitize_text_field( wp_unslash( $_POST['package'] ) ) : '';
		$name          = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$product_type  = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';

		if ( ! empty( $download_link ) ) {
			// Suppress feedback.
			ob_start();

			try {

				$temp_file = download_url( $download_link, 60 );

				if ( ! is_wp_error( $temp_file ) ) {
					require_once ABSPATH . '/wp-admin/includes/file.php';
					WP_Filesystem();

					if ( 'plugin' === $product_type ) {
						$destination_dir = WP_CONTENT_DIR . '/plugins';
					} elseif ( 'theme' === $product_type ) {
						$destination_dir = WP_CONTENT_DIR . '/themes';
					} else {
						$destination_dir = WP_CONTENT_DIR;
					}

					$temp_dir = WP_CONTENT_DIR . '/slswcclient_temp_downloads';
					if ( ! $wp_filesystem->is_dir( $temp_dir ) ) {
						$wp_filesystem->mkdir( $temp_dir, FS_CHMOD_DIR );
					}

					$file_name   = $slug . '.zip';
					$destination = $temp_dir . $file_name;

					if ( $wp_filesystem->exists( $temp_file ) ) {
						$wp_filesystem->move( $temp_file, $destination, true );
					}

					if ( $wp_filesystem->exists( $destination ) ) {
						$unzipfile = unzip_file( $destination, $destination_dir );

						if ( $unzipfile ) {
							$deleted = $wp_filesystem->delete( $destination );
							wp_send_json_success(
								array(
									'message' => sprintf(
										// translators: %s - the name of the plugin/theme.
										__( 'Successfully installed new version of %s', 'slswcclient' ),
										$name
									),
								)
							);
						} else {
							wp_send_json_error(
								array(
									'slug'    => $slug,
									'message' => __( 'Installation failed. There was an error extracting the downloaded file.', 'slswcclient' ),
								)
							);
						}
					}
				}
			} catch ( Exception $e ) {
				wp_send_json_error(
					array(
						'slug'    => $slug . '_install_error',
						'message' => sprintf(
							// translators: 1: theme slug, 2: error message, 3: URL to install theme manually.
							__( '%1$s could not be installed (%2$s). <a href="%3$s">Please install it manually by clicking here.</a>', 'slswcclient' ),
							$slug,
							$e->getMessage(),
							esc_url( admin_url( 'update.php?action=install-' . $product_type . '&' . $product_type . '=' . $slug . '&_wpnonce=' . wp_create_nonce( 'install-' . $product_type . '_' . $slug ) ) )
						),
					)
				);
			}

			wp_send_json_error( array( 'message' => __( 'No action taken.', 'slswcclient' ) ) );

			// Discard feedback.
			ob_end_clean();
		}

		wp_send_json(
			array(
				'message' => __( 'Failed to install product. Download link not provided or is invalid.', 'slswcclient' ),
			)
		);
	}

	/**
	 * Check if a url qualifies as localhost, staging or development environment.
	 *
	 * @param string $url The url to be checked.
	 * @param string $environment The user specified environment of the url.
	 * @return boolean
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public static function is_dev( $url = '', $environment = '' ) {
		$is_dev = false;

		if ( $environment === 'staging' ) {
			return apply_filters( 'slswc_client_is_dev', true, $url, $environment );
		}

		if ( 'live' === $environment ) {
			return apply_filters( 'slswc_client_is_dev', false, $url, $environment );
		}

		// Trim the url.
		$url = strtolower( trim( $url ) );

		// Add the scheme so we can use parse_url.
		if ( false === strpos( $url, 'http://' ) && false === strpos( $url, 'https://' ) ) {
			$url = 'http://' . $url;
		}

		$url_parts = wp_parse_url( $url );
		$host      = ! empty( $url_parts['host'] ) ? $url_parts['host'] : false;

		if ( empty( $url ) || ! $host ) {
			return apply_filters( 'slswc_client_is_dev', false );
		}

		$is_ip_local = self::is_ip_local( $host );

		$check_tlds = apply_filters( 'slswc_client_validate_tlds', true );
		$is_tld_dev = false;

		if ( $check_tlds ) {
			$is_tld_dev = self::is_tld_dev( $host );
		}

		$is_subdomain_dev = self::is_subdomain_dev( $host );

		$is_dev = ( $is_ip_local || $is_tld_dev || $is_subdomain_dev ) ? true : false;

		return apply_filters( 'slswc_client_is_dev', $is_dev, $url, $environment );
	}

	/**
	 * Check if a host's IP address is within the local IP range.
	 *
	 * @param string $host The host to be checked.
	 * @return boolean
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public static function is_ip_local( $host ) {
		if ( 'localhost' === $host ) {
			return true;
		}

		if ( false !== ip2long( $host ) ) {
			if ( ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return true;
			}
		}

		return apply_filters( 'slswc_client_is_ip_local', false, $host );
	}

	/**
	 * Check if a host's TLD is a development or local tld
	 *
	 * @param string $host The host to be checked.
	 * @return boolean
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public static function is_tld_dev( $host ) {
		$tlds_to_check = apply_filters(
			'slswc_client_url_tlds',
			array(
				'.dev',
				'.local',
				'.test',
			)
		);

		foreach ( $tlds_to_check as $tld ) {
			if ( false !== strpos( $host, $tld ) ) {
				return true;
			}
		}

		return apply_filters( 'slswc_client_is_tld_dev', false, $host );
	}

	/**
	 * Check if a domain contains development subdomain.
	 *
	 * @param string $host The domain to be checked.
	 * @return boolean
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public static function is_subdomain_dev( $host ) {
		if ( substr_count( $host, '.' ) <= 1 ) {
			return false;
		}

		$subdomains_to_check = apply_filters(
			'slswc_client_url_subdomains',
			array(
				'dev.',
				'*.staging.',
				'*.test.',
				'staging-*.',
				'*.wpengine.com',
				'*.easywp.com',
			)
		);

		foreach ( $subdomains_to_check as $subdomain ) {

			$subdomain = str_replace( '.', '(.)', $subdomain );
			$subdomain = str_replace( array( '*', '(.)' ), '(.*)', $subdomain );

			if ( preg_match( '/^(' . $subdomain . ')/', $host ) ) {
				return true;
			}
		}

		return apply_filters( 'slswc_client_is_subdomain_dev', false, $host );
	}

	/**
	 * The available license status types
	 *
	 * @since   1.1.0 - Moved from ClientManager class
	 * @version 1.1.0
	 */
	public static function license_status_types() {

		return apply_filters(
			'slswc_license_status_types',
			array(
				'valid'           => __( 'Valid', 'slswcclient' ),
				'deactivated'     => __( 'Deactivated', 'slswcclient' ),
				'max_activations' => __( 'Max Activations reached', 'slswcclient' ),
				'invalid'         => __( 'Invalid', 'slswcclient' ),
				'inactive'        => __( 'Inactive', 'slswcclient' ),
				'active'          => __( 'Active', 'slswcclient' ),
				'expiring'        => __( 'Expiring', 'slswcclient' ),
				'expired'         => __( 'Expired', 'slswcclient' ),
			)
		);
	}

	/**
	 * Class logger so that we can keep our debug and logging information cleaner
	 *
	 * @version 1.1.0
	 * @since   1.1.0
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
	}
}
