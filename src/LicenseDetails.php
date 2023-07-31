<?php

namespace Madvault\Slswc\Client;

class LicenseDetails {
	/**
	 * License server URL
	 *
	 * @var string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $license_server_url;
	/**
	 * The plugin details
	 *
	 * @var array
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $plugin_details = array();

	/**
	 * The option key for saving license details.
	 *
	 * @var string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $option_name = '';

	// public $environment;

	// public $version;

	public $client;

	/**
	 * Plugin file
	 *
	 * @var string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $plugin_file;

	/**
	 * The license details
	 *
	 * @var array
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $license_details = array();

	/**
	 * Construct the instance of the class
	 *
	 * @param string $plugin_file    The plugin file.
	 * @param array  $plugin_details The plugin details.
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function __construct( $plugin_file, $plugin_details  = array() ) {
		$this->plugin_file     = $plugin_file;

		if ( defined( 'SLSWC_LICENSE_SERVER_URL' ) ) {
			$this->license_server_url = SLSWC_LICENSE_SERVER_URL;
		}

		

		$this->plugin_details = Helper::recursive_parse_args(
			$plugin_details,
			$this->get_default_license_details()
		);

		$this->client = new ApiClient(
			$this->license_server_url,
			$this->plugin_details['slug'],
		);

		$this->set_license_details();

		if ( $this->license_details['license_status'] !== 'active' ) {
			$this->validate_license();
		}
	}	

	/**
	 * --------------------------------------------------------------------------
	 * Getters
	 * --------------------------------------------------------------------------
	 *
	 * Methods for getting object properties.
	 */
	/**
	 * Check if staging activated
	 *
	 * @param string $environment environment to get status.
	 * @return boolean
	 */
	public function get_active_status( $environment ) {
		$options = $this->license_details;
		if ( ! isset( $options['active_status'] ) ) {
			$options['active_status'] = array(
				'live'    => false,
				'staging' => false,
			);
		}

		if ( ! $environment ) {
			return false;
		}

		$active_status = $options['active_status'][ $environment ];

		return is_bool( $active_status )
			? $active_status
			: (
				'yes' === strtolower( $active_status )
				|| 1 === $active_status
				|| 'true' === strtolower( $active_status )
				|| '1' === $active_status
			);
	}
	
	/**
	 * Get default license options.
	 *
	 * @param array $args Options to override the defaults.
	 * @return  array
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function get_default_license_details( $args = array() ) {
		$default_options = array(
			'license_status'     => 'inactive',
			'license_key'        => '',
			'license_expires'    => '',
			'current_version'    => '',
			'environment'        => '',
			'active_status'      => array(
				'live'    => 'no',
				'staging' => 'no',
			),
			//'deactivate_license' => 'deactivate_license',
		);

		if ( ! empty( $args ) ) {
			$default_options = wp_parse_args( $args, $default_options );
		}

		return apply_filters( 'slswc_client_default_license_options', $default_options );
	}

  /**
	 * Set the license details
	 *
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function set_license_details() {
		$this->set_slug( $this->plugin_details['slug'] );
		$this->set_domain( $this->plugin_details['domain'] );
		$this->set_license_status( $this->plugin_details['license_status'] );
		$this->set_license_key( $this->plugin_details['license_key'] );
		$this->set_license_expires( $this->plugin_details['license_expires'] );
		$this->set_current_version( $this->plugin_details['version']  );
		$this->set_active_status( $this->plugin_details['active_status'] );
	}

	/**
	 * Get the software slug.
	 *
	 * @return string										
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function get_slug() {
		return $this->plugin_details['slug'];
	}

	/**
	 * Get the domain
	 *
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function get_domain() {
		return $this->license_details['domain'];
	}

	/**
	 * Get the license status.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function get_license_status() {
		return $this->license_details['license_status'];
	}

	/**
	 * Get the license key
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function get_license_key() {
		return $this->license_details['license_key'];
	}


	/**
	 * Get the license expiry
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function get_license_expires() {
		return $this->license_details['license_expires'];
	}

	/**
	 * Get the environment
	 *
	 * @return string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function get_environment() {
		return $this->license_details['environment'];
	}

	/**
	 * Get the current version
	 *
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function get_current_version() {
		return $this->license_details['version'];
	}

	/**
	 * Get the license details.
	 *
	 * @return array
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function get_license_details() {
		return $this->license_details;
	}

	/**
	 * --------------------------------------------------------------------------
	 * Setters
	 * --------------------------------------------------------------------------
	 *
	 * Methods for setting object properties.
	 */

	/**
	 * Set the software slug.
	 *
	 * @param string $slug
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function set_slug( $slug )	{
		$this->license_details['slug'] = $slug;
	}

	/**
	 * Set the domain
	 *
	 * @param string $domain
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function set_domain( $domain ) {
		$this->license_details['domain'] = $domain;
	}

  /**
	 * Set the license status
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $license_status license status.
	 */
	public function set_license_status( $license_status ) {
		$this->license_details['license_status'] = $license_status;
	}

	/**
	 * Set the license key
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $license_key License key.
	 */
	public function set_license_key( $license_key ) {
		$this->license_details['license_key'] = $license_key;
	}

	/**
	 * Set the license expires.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $license_expires License expiry date.
	 */
	public function set_license_expires( $license_expires ) {
		$this->license_details['license_expires'] = $license_expires;
	}

	/**
	 * Set the current version
	 *
	 * @param string $version The version to set.
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function set_current_version( $version ) {
		$this->license_details['version'] = $version;
	}

	/**
	 * Set the environment
	 *
	 * @param string $environment The environment to set.
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function set_environment( $environment ) {
		$this->license_details['environment'] = $environment;
	}

	/**
	 * Set the active status
	 *
	 * @param array $active_status The active status to set.
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function set_active_status( $active_status ) {
		$this->license_details['active_status'] = $active_status;
	}

	/**
	 * Save the license details.
	 *
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function save() {
		update_option( $this->option_name, $this->license_details );
	}

	/**
	 * 
	 */
	/**
	 * Validate the license is active and if not, set the status and return false
	 *
	 * @since 1.0.0
	 * @param object $response_body Response body.
	 */
	public function check_license( $response_body ) {

		$status = is_array( $response_body) ? $response_body['status'] : $response_body->status;

		if ( 'active' === $status || 'expiring' === $status ) {
			return true;
		}

		if ( ! is_numeric( $status ) ) {
			$this->set_license_status( $status );
			$this->set_license_expires( $response_body->expires );
			$this->save();
		}

		return false;
	}

	/**
	 * The available license status types.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function license_status_types() {

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
	 * Validate the license key information sent from the form.
	 *
	 * @since   1.0.0
	 * @version 1.0.2
	 * @param array $input the input passed from the request.
	 */
	public function validate_license( $input = array() ) {
		$license = $this->get_license_details();
		$message = null;

		// Reset the license data if the license key has changed.
		if ( isset( $input['license_key'] ) && $license['license_key'] !== $input['license_key'] ) {
			$license               = $this->get_default_license_details();
			$this->license_details = $license;
		}

		$environment   = isset( $license['environment'] ) ? $license['environment'] : '';
		$active_status = $this->get_active_status( $environment );

		$this->license_details['license_key'] = isset( $input['license_key'] )
			? $input['license_key']
			: $this->get_license_key();
		$license                              = wp_parse_args( $input, $license );

		Helper::log( "Validate license:: key={$license['license_key']}, environment=$environment, status=$active_status" );

		$response = null;
		$action   = array_key_exists( 'deactivate_license', $input ) ? 'deactivate' : 'activate';

		if ( $active_status && 'activate' === $action ) {
			$license['license_status'] = 'active';
		}

		if ( 'activate' === $action && ! $active_status ) {
			Helper::log( 'Activating. current status is: ' . $this->get_license_status() );

			unset( $license['deactivate_license'] );
			$this->license_details = $license;

			$response = $this->client->request( 'activate', $license );
		} elseif ( 'deactivate' === $action ) {
			Helper::log( 'Deactivating license. current status is: ' . $this->get_license_status() );

			$response = $this->client->request( 'deactivate', $license );
		} else {
			unset( $license['deactivate_license'] );
			$this->license_details = $license;

			$response = $this->client->request( 'check_license', $license );
		}

		if ( is_null( $response ) ) {
			$message = __(
				'Error: Your license might be invalid or there was an unknown error on the license server. Please try again and contact support if this issue persists.',
				'slswcclient'
			);
			update_option( $this->option_name, $license );
			return array (
				'status'   => 'bad_request',
				'message'  => $message,
				'response' => $response
			);
		}

		// phpcs:ignore
		if ( ! $this->client->check_response_status( $response ) ) {
			update_option( $this->option_name, $license );
			return array(
				'status'   => 'invalid',
				'message'  => is_array( $response ) ? $response['response'] : $response->response,
				'response' => $response,
			);
		}

		$license['license_key']                   = isset( $input['license_key'] ) ? $input['license_key'] : $this->get_license_key();
		$license['license_status']                = $response->domain->status;
		$license['domain']                        = $response->domain;
		$license['license_expires']               = $response->expires;
		$license['active_status'][ $environment ] = 'activate' === $action && 'active' === $response->domain->status ? 'yes' : 'no';

		$domain_status = $response->domain->status;

		$messages = $this->license_status_types();

		if ( ( 'valid' === $domain_status || 'active' === $domain_status ) && 'activate' === $action ) {
			$message = __( 'License activated.', 'slswcclient' );
		} elseif ( 'active' !== $domain_status && 'activate' === $action ) {
			$message = sprintf(
				__( 'Failed to activate license. %s', 'slswcclient' ),
				$messages[ $domain_status ]
			);
		} elseif ( 'deactivate' === $action && 'deactivated' === $domain_status ) {
			$message = __( 'License Deactivated', 'slswcclient' );
		} elseif ( 'deactivate' === $action && 'deactivate' !== $domain_status ) {
			$message = sprintf(
				// translators: %s - The message describing the license status.
				__( 'Unable to deactivate license. Please deactivate on the store. %s', 'slswcclient' ),
				$messages[ $domain_status ]
			);
		} else {
			$message = $messages[ $response->status ];
		}

		Helper::log( $message );

		update_option( $this->option_name, $license );

		Helper::log( $license );

		return array(
			'message'  => $message,
			'options'  => $license,
			'status'   => $domain_status,
			'response' => $response
		);
	}
}