<?php

namespace Madvault\Slswc\Client;

class LicenseDetails {
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

	public $plugin_file;

	/**
	 * The license details
	 *
	 * @var array
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $license_details = array();

	public function __construct( $plugin_file, $license_details  = array() ) {
		$this->plugin_file = $plugin_file;
		
		$this->license_details = Helper::recursive_parse_args(
			$license_details,
			$this->get_default_license_options()
		);
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
	 * Get the default args
	 *
	 * @return  array $args
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public static function get_default_args() {
		return array(
			'update_interval' => 12,
			'debug'           => false,
			'environment'     => '',
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
	public function get_default_license_options( $args = array() ) {
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

	} // set_license_key

	/**
	 * Set the license expires.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $license_expires License expiry date.
	 */
	public function set_license_expires( $license_expires ) {

		$this->license_details['license_expires'] = $license_expires;

	} // set_license_expires

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
	public function validate_license( $input ) {
		$options = $this->license_details;
		$message = null;

		// Reset the license data if the license key has changed.
		if ( $options['license_key'] !== $input['license_key'] ) {
			$options               = $this->get_default_license_options();
			$this->license_details = $options;
		}

		$environment   = isset( $input['environment'] ) ? $input['environment'] : '';
		$active_status = $this->get_active_status( $environment );

		$this->license_details['license_key'] = $input['license_key'];
		$options                              = wp_parse_args( $input, $options );

		Helper::log( "Validate license:: key={$input['license_key']}, environment=$environment, status=$active_status" );

		$response = null;
		$action   = array_key_exists( 'deactivate_license', $input ) ? 'deactivate' : 'activate';

		if ( $active_status && 'activate' === $action ) {
			$options['license_status'] = 'active';
		}

		if ( 'activate' === $action && ! $active_status ) {
			Helper::log( 'Activating. current status is: ' . $this->get_license_status() );

			unset( $options['deactivate_license'] );
			$this->license_details = $options;

			$response = $this->client->request( 'activate' );
		} elseif ( 'deactivate' === $action ) {
			Helper::log( 'Deactivating license. current status is: ' . $this->get_license_status() );

			$response = $this->client->request( 'deactivate' );
		} else {
			unset( $options['deactivate_license'] );
			$this->license_details = $options;

			$response = $this->client->request( 'check_license' );
		}

		if ( is_null( $response ) ) {
			$message = __(
				'Error: Your license might be invalid or there was an unknown error on the license server. Please try again and contact support if this issue persists.',
				'slswcclient'
			);
			update_option( $this->option_name, $options );
			return array (
				'status'   => 'bad_request',
				'message'  => $message,
				'response' => $response
			);
		}

		// phpcs:ignore
		if ( ! $this->client->check_response_status( $response ) ) {
			update_option( $this->option_name, $options );
			return array(
				'status'   => 'invalid',
				'message'  => $response['response'],
				'response' => $response,
			);
		}

		$options['license_key']                   = $input['license_key'];
		$options['license_status']                = $response->domain->status;
		$options['domain']                        = $response->domain;
		$options['license_expires']               = $response->expires;
		$options['active_status'][ $environment ] = 'activate' === $action && 'active' === $response->domain->status ? 'yes' : 'no';

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

		update_option( $this->option_name, $options );

		Helper::log( $options );

		return array(
			'message'  => $message,
			'options'  => $options,
			'status'   => $domain_status,
			'response' => $response
		);
	}
}