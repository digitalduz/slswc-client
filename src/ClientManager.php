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
class ClientManager {
	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Version - current plugin version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public static $version;

	/**
	 * License URL - The base URL for your WooCommerce install.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public static $license_server_url;

	/**
	 * The plugin slug to check for updates with the server.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public static $slug;

	/**
	 * Plugin text domain.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public static $text_domain;

	/**
	 * List of locally installed plugins
	 *
	 * @var     array $plugins The list of plugins.
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public static $plugins;

	/**
	 * List of locally installed themes.
	 *
	 * @var     array $themes The list of themes.
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public static $themes;

	/**
	 * List of products
	 *
	 * @var     array
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public static $products;

	/**
	 * Status update messages
	 *
	 * @var array
	 * @version 1.0.1
	 * @since   1.0.1
	 */
	public static $messages = array();

	/**
	 * The localization strings
	 *
	 * @var array
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public static $localization = array();

	/**
	 * Return instance of this class
	 *
	 * @param   string $license_server_url The url to the license server.
	 * @param   string $slug The software slug.
	 * @param   string $text_domain The software text domain.
	 * @return  object
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public static function get_instance( $license_server_url, $slug, $text_domain ) {
		self::$license_server_url = $license_server_url;
		self::$slug               = $slug;
		self::$text_domain        = $text_domain;

		if ( null === self::$instance ) {
			self::$instance = new self( self::$license_server_url, self::$slug, 'slswcclient' );
		}

		return self::$instance;
	} // get_instance

	/**
	 * Initialize the class actions
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $license_server_url - The base url to your WooCommerce shop.
	 * @param string $slug - The software slug.
	 * @param string $text_domain - The plugin's text domain.
	 */
	private function __construct( $license_server_url, $slug, $text_domain ) {
		global $slswc_updater;
		self::$license_server_url = apply_filters( 'slswc_client_manager_license_server_url', $license_server_url );
		self::$slug               = $slug;
		self::$text_domain        = $text_domain;

		self::$plugins = $slswc_updater->get_local_plugins();
		self::$themes  = $slswc_updater->get_local_themes();

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'wp_ajax_slswc_install_product', array( $this, 'product_background_installer' ) );

		if ( self::is_products_page() ) {
			add_action( 'admin_footer', array( $this, 'admin_footer_products_script' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
		}

		if ( self::is_licenses_tab() ) {
			add_action( 'admin_footer', array( $this, 'admin_footer_licenses_script' ) );
		}

		self::$localization = array(
			'ajax_url'        => esc_url( admin_url( 'admin-ajax.php' ) ),
			'loader_url'      => esc_url( admin_url( 'images/loading.gif' ) ),				
			'text_activate'   => esc_attr( __( 'Activate', 'slswcclient' ) ),
			'text_deactivate' => esc_attr( __( 'Deactivate', 'slswcclient' ) ),
			'text_done'       => esc_attr( __( 'Done', 'slswcclient' ) ),				
			'text_processing' => esc_attr( __( 'Processing', 'slswcclient' ) ),
		);
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return  void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public static function admin_enqueue_scripts() {
		if ( self::is_products_page() ) {
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_style( 'thickbox' );
		}
	}

	/**
	 * Check if the current page is a product list page.
	 *
	 * @return  boolean
	 * @version 1.0.3
	 * @since   1.0.0
	 */
	public static function is_products_page() {

		$tabs = array( 'plugins', 'themes' );
		$page = 'slswc_license_manager';
		// phpcs:disable
		$is_page = isset( $_GET['page'] ) && $page === $_GET['page'] ? true : false;
		$is_tab  = isset( $_GET['tab'] ) && in_array( wp_unslash( $_GET['tab'] ), $tabs, true ) ? true : false;
		// phpcs:enable
		if ( is_admin() && $is_page && $is_tab ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if we are on the licenses tab on the license manager page.
	 *
	 * @return boolean
	 * @version 1.0.3
	 * @since   1.0.3
	 */
	public static function is_licenses_tab() {
		$tab  = self::get_tab();
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		return is_admin() && $tab == 'licenses' && $page == 'slswc_license_manager';
	}

	/**
	 * Add script to admin footer.
	 *
	 * @return  void
	 * @version 1.0.3
	 * @since   1.0.3 - Rewrote the script
	 */
	public static function admin_footer_products_script() {
		?>
	<script type="text/javascript">
		jQuery( function( $ ){

			"use strict";

			$(document).ready( function() {
				slswcClient.init();
			});

			window.slswcClientOptions = <?php echo wp_json_encode( self::$localization ); ?>

			const slswcClient = {
				init: function() {
					slswcClient.installer();
				},
				installer: function() {
					if ( ! $( '.slswc-install-now' ) && ! $( '.slswc-update-now' ) ) {
						return;
					}

					$('.slswc-install-now, .slswc-update-now').on( 'click', function(e){
						e.preventDefault();
						let $el = $(this);
						let download_url = $(this).data('download_url');
						let name = $(this).data('name');
						let slug = $(this).data('slug');
						let type = $(this).data('type');
						let label = $(this).html();
						let nonce = $(this).data('nonce');
						
						let action_label = window.slswcClientOptions.processing_label;
						$(this).html(`<img src="${window.slswcClientOptions.loaderUrl}" /> ` + action_label );
						$.ajax({
							url: window.ajaxUrl,
							data: {
								action:  'slswc_install_product',
								download_url: download_url,
								name:    name,
								slug:    slug,
								type:    type,
								nonce:   nonce
							},
							dataType: 'json',
							type: 'POST',
							success: function( response ) {
								if ( response.success ) {
									$('#slswc-product-install-message p').html( response.data.message );
									$('#slswc-product-install-message').addClass('updated').show();
								} else {
									$('#slswc-product-install-message p').html( response.data.message );
									$('#slswc-product-install-message').addClass('notice-warning').show();
								}
								$el.html( slswcClientOptions.done_label );
								$el.attr('disabled', 'disabled');
							},
							error: function( error ) {
								$('#slswc-product-install-message p').html( error.data.message );
								$('#slswc-product-install-message').addClass('notice-error').show();
							}
						});
					});
				}
			}
		} );
	</script>
		<?php
	}

	/**
	 * Add footer script to manage licenses.
	 *
	 * @return void
	 * @version 1.0.3
	 * @since   1.0.3
	 */
	public function admin_footer_licenses_script() {
		?>
		<script type="text/javascript">
			jQuery( function( $ ){

				"use strict";

				$(document).ready( function() {
					slswcClient.init();
				});

				window.slswcClientOptions = <?php echo wp_json_encode( self::$localization ); ?>;

				const slswcClient = {
					init: function() {
						$('.force-client-environment').on( 'click', slswcClient.toggleEnvironment );
						$('.license-action').on('click', slswcClient.processAction );
					},
					toggleEnvironment: function(event) {
						const id = $(this).attr('id');
						const slug = $(this).data('slug');

						if ( $(this).is(':checked') ) {
							$('#'+ slug + '_environment').show();
							$('#'+ slug + '_environment-label').show();
						} else {
							$('#'+ slug + '_environment').hide();
							$('#'+ slug + '_environment-label').hide();
						}
					},
					processAction: function(e) {
						e.preventDefault();

						let button = $(this);

						let currentLabel = $(button).html();
						$(button).html(`<img src="${window.slswcClientOptions.loader_url}" width="12" height="12"/> Processing`);

						let slug = $(this).data('slug');
						let license_key = $(this).data('license_key');
						let license_action = $(this).data('action');
						let nonce = $(this).data('nonce');
						let domain = $(this).data('domain');
						let version = $(this).data('version');
						let environment = '';

						if ( $( '#'+ slug + '_force-client-environment' ).is(':checked') && $( '#'+ slug + '_environment').is(':visible') ) {
							environment = $( '#'+ slug + '_environment' ).val();
						}

						console.log(slug, license_key, license_action, domain, nonce, version, environment);

						$.ajax({
							url: window.slswcClientOptions.ajax_url,
							data: {
								action: 'slswc_activate_license',
								license_action: license_action,
								license_key: license_key,
								slug:        slug,
								domain:      domain,
								version:     version,
								environment: environment,
								nonce:       nonce
							},
							dataType: 'json',
							type: 'POST',
							success: function(response) {
								const message = `The license for ${slug} activated successfully`;
								
								$(button).html(currentLabel);

								$('#'+slug+'_license_status').val(response.status);
								$('#'+slug+'_license_status_text').html(response.status);

								if ( response.success == false ) {
									slswcClient.notice(response.data.message, 'error');
									return;
								}

								switch (response.status) {
									case 'active':
										slswcClient.notice(response.message);
										$(button).html(window.slswcClientOptions.text_deactivate);
										$(button).data('action', 'deactivate');
										break;
									default:
										slswcClient.notice(response.message, 'error');
										$(button).html(window.slswcClientOptions.text_activate);
										$(button).data('action', 'activate');
										break;
								}
							},
							error: function(error) {
								const message = error.message;
								slswcClient.notice( message, 'error' );
								$(button).html(currentLabel);
							}
						});
					},
					notice: function(message, type = 'success', isDismissible = true) {
						let notice = `<div class="${type} notice is-dismissible"><p>${message}</p>`;

						if ( isDismissible ) {
							notice += `<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>`;
						}
								
						notice += `</div>`;

						$('#license-action-response-message').html(notice);
					}
				}
			});
		</script>
	<?php
	}
		
	/**
	 * ------------------------------------------------------------------
	 * Output Functions
	 * ------------------------------------------------------------------
	 */

	/**
	 * Add the admin menu to the dashboard
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function add_admin_menu() {
		$page = add_options_page(
			__( 'License Manager', 'slswcclient' ),
			__( 'License Manager', 'slswcclient' ),
			'manage_options',
			'slswc_license_manager',
			array( $this, 'show_installed_products' )
		);
	}

	/**
	 * List all products installed on this server.
	 *
	 * @return  void
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public static function show_installed_products() {
		$license_admin_url = admin_url( 'options-general.php?page=slswc_license_manager' );
		// phpcs:ignore
		$tab = self::get_tab();

		?>
		<style>
			.slswc-product-thumbnail:before {font-size: 128px;}
			.slswc-plugin-card-bottom {display: flex;}
			.slswc-plugin-card-bottom div {width: 45%;}
			.slswc-plugin-card-bottom div.column-updated {float:left;text-align:left;}
			.slswc-plugin-card-bottom div.column-compatibility {float:right;text-align:right;}
		</style>
		<div class="wrap plugin-install-tab">				
			<div id="slswc-product-install-message" class="notice inline hidden"><p></p></div>
			<h1><?php esc_attr_e( 'Licensed Plugins and Themes', 'slswcclient' ); ?></h1>
			<?php

			if ( isset( $_POST['save_api_keys_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['save_api_keys_nonce'] ) ), 'save_api_keys' ) ) {

				$username        = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
				$consumer_key    = isset( $_POST['consumer_key'] ) ? sanitize_text_field( wp_unslash( $_POST['consumer_key'] ) ) : '';
				$consumer_secret = isset( $_POST['consumer_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['consumer_secret'] ) ) : '';

				$save_username        = update_option( 'slswc_api_username', $username );
				$save_consumer_key    = update_option( 'slswc_consumer_key', $consumer_key );
				$save_consumer_secret = update_option( 'slswc_consumer_secret', $consumer_secret );

				if ( $save_username && $save_consumer_key && $save_consumer_secret ) {
					?>
					<div class="updated"><p><?php esc_attr_e( 'API Settings saved', 'slswcclient' ); ?></p></div>
					<?php
				}
			}

			if ( ! empty( $_POST['connect_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['connect_nonce'] ) ), 'connect' ) ) {
				$connected = self::connect();
				if ( $connected ) {
					?>
					<div class="updated"><p>
					<?php
					esc_attr_e( 'API Connected successfully.', 'slswcclient' );
					?>
					</p></div>
					<?php
				} else {
					?>
					<div class="error notice is-dismissible"><p>
					<?php
					esc_attr_e( 'API connection failed. Please check your keys and try again.', 'slswcclient' );
					?>
					</p></div>
					<?php
				}
			}

			if ( ! empty( $_POST['reset_api_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['reset_api_settings_nonce'] ) ), 'reset_api_settings' ) ) {
				$deleted_username        = delete_option( 'slswc_api_username' );
				$deleted_consumer_key    = delete_option( 'slswc_consumer_key' );
				$deleted_consumer_secret = delete_option( 'slswc_consumer_secret' );

				if ( $deleted_username && $deleted_consumer_key && $deleted_consumer_secret ) {
					?>
					<p class="updated">
					<?php
					esc_attr_e( 'API Keys successfully.', 'slswcclient' );
					?>
					</p>
					<?php
				} else {
					?>
					<p class="updated">
					<?php
					esc_attr_e( 'API Keys not reset.', 'slswcclient' );
					?>
					</p>
					<?php
				}
			}

			if ( ! empty( $_POST['disconnect_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['disconnect_nonce'] ) ), 'disconnect' ) ) {
				update_option( 'slswc_api_connected', 'no' );
			}

			$_all_products = array_merge( self::$plugins, self::$themes );
			foreach ( $_all_products as $_product ) {
				$option_name = esc_attr( $_product['slug'] ) . '_license_manager';

				settings_errors( $option_name );
			}
			?>
			<div class="wp-filter">
				<ul class="filter-links">
					<li>
						<a href="<?php echo esc_attr( $license_admin_url ); ?>&tab=licenses"
							class="<?php echo esc_attr( ( 'licenses' === $tab || empty( $tab ) ) ? 'current' : '' ); ?>">
							<?php esc_attr_e( 'Licenses', 'slswcclient' ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_attr( $license_admin_url ); ?>&tab=plugins"
							class="<?php echo ( 'plugins' === $tab ) ? 'current' : ''; ?>">
							<?php esc_attr_e( 'Plugins', 'slswcclient' ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_attr( $license_admin_url ); ?>&tab=themes"
							class="<?php echo ( 'themes' === $tab ) ? 'current' : ''; ?>">
							<?php esc_attr_e( 'Themes', 'slswcclient' ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_attr( $license_admin_url ); ?>&tab=api"
							class="<?php echo ( 'api' === $tab ) ? 'current' : ''; ?>">
							<?php esc_attr_e( 'API', 'slswcclient' ); ?>
						</a>
					</li>
				</ul>
			</div>
			<br class="clear" />

			<div class="tablenav-top"></div>
			<?php if ( 'licenses' === $tab || empty( $tab ) ) : ?>
			<div id="licenses">
				<?php self::licenses_form(); ?>
			</div>

			<?php elseif ( 'plugins' === $tab ) : ?>
			<div id="plugins" class="wp-list-table widefat plugin-install">
				<?php self::list_products( self::$plugins ); ?>
			</div>

			<?php elseif ( 'themes' === $tab ) : ?>
			<div id="themes" class="wp-list-table widefat plugin-install">
				<?php self::list_products( self::$themes ); ?>
			</div>

			<?php else : ?>
			<div id="api">
				<?php self::api_form(); ?>
			</div>
				<?php
			endif;
			?>
			<?php
	}

	/**
	 * Output licenses form
	 *
	 * @return  void
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public static function licenses_form() {
		?>
		<style>
		.licenses-table{margin-top: 9px;}
		.licenses-table th, .licenses-table td {padding: 8px 10px;}
		.licenses-table .actions {vertical-align: middle;width: 20px;}
		.plugin-card input[type="text"], .plugin-card select{
			width: 100%;
		}
		.plugin-card .column-license-key {
			display: flex;
			flex-direction: column;
		}
		.plugin-card .column-license-key label {
			margin-bottom: 5px;
			font-weight: 600;
		}
		.plugin-card label {
			margin-top: 10px
		}
		.chip {
			align-items: center;
			display: inline-flex;
			justify-content: center;
			background-color: #d1d5db;
			border-radius: 9999px;
			padding: 0.25rem 0.5rem;
		}

		.chip_content {
			margin-right: 0.25rem;
		}
		.chip.active {
			background-color: green;
			color: #ffffff;
		}
		</style>
		<?php

		if ( ! empty( $_POST['licenses'] ) && ! empty( $_POST['save_licenses_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['save_licenses_nonce'] ) ), 'save_licenses' ) ) {
			// phpcs:ignore
			$post_licenses = isset( $_POST['licenses'] ) ? wp_unslash( $_POST['licenses'] ) : array();

			if ( ! empty( $post_licenses ) ) {
				foreach ( $post_licenses as $slug => $license_details ) {
					$license_details = Helper::recursive_parse_args(
						$license_details,
						array(
							'license_status'  => 'inactive',
							'license_key'     => '',
							'license_expires' => '',
							'current_version' => self::$version,
							'environment'     => '',
						)
					);

					do_action( "slswc_save_license_{$slug}", $license_details );
				}
			}
		}

		self::display_messages();
		?>
		<h2 class="screen-reader-text"><?php echo esc_attr( __( 'Licenses', 'slswcclient' ) ); ?></h2>
		<div id="license-action-response-message"></div>
		<form name="licenses-form" action="" method="post">
			<?php wp_nonce_field( 'save_licenses', 'save_licenses_nonce' ); ?>
			<div id="the-list">
				<?php if ( ! empty( self::$plugins ) ) : 
					self::licenses_rows( self::$plugins );
					do_action( 'slswc_after_plugins_licenses_rows' );
				endif;

				if ( ! empty( self::$themes ) ) :
					self::licenses_rows( self::$themes );
					do_action( 'slswc_after_themes_licenses_list' );
					endif;
				?>
				<?php do_action( 'slswc_after_products_licenses_list', self::$plugins, self::$themes ); ?>
			</div>
		</form>
		<?php
	}

	/**
	 * Licenses rows output
	 *
	 * @param   array $products The list of software products.
	 * @return  void
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public static function licenses_rows( $products ) {

		foreach ( $products as $product ) :
			$slug         = esc_attr( $product['slug'] );
			$option_name  = $slug . '_license_manager';
			$license_info = get_option( $option_name, array() );
			$product_name = ! empty( $product['name'] ) ? $product['name'] : $product['title'];

			$has_license_info    = empty( $license_info ) ? false : true;
			$license_key         = $has_license_info ? trim( $license_info['license_key'] ) : '';
			$current_version     = $has_license_info ? trim( $license_info['current_version'] ) : '';
			$license_status      = $has_license_info ? trim( $license_info['license_status'] ) : '';
			$license_expires     = $has_license_info ? trim( $license_info['license_expires'] ) : '';
			$license_environment = $has_license_info ? trim( $license_info['environment'] ) : '';
			$active_status       = $has_license_info ? ( array_key_exists( 'active_status', $license_info ) && 'yes' === $license_info['active_status'][ $license_environment ] ? true : false ) : false;
			
			$is_active = wc_string_to_bool( $active_status );

			$chip_class = $is_active ? 'active' : 'inactive';
			?>
			<div class="plugin-card plugin-card-<?php echo esc_attr( $product['slug'] ); ?>">
				<div class="plugin-card-top">
					<div class="column-name">
						<h3><?php echo esc_attr( $product_name ); ?></h3>
					</div>
					<div class="action-links">
						<ul class="plugin-action-buttons">
							<li>
								<span class="chip <?php echo esc_attr( $chip_class ); ?>" id="<?php echo esc_attr( $slug ); ?>_license_status_text">
									<span class="chip-content">
										<?php self::license_status_field( $license_status ); ?>
									</span>
								</span>
								<input
									type="hidden"
									name="licenses[<?php echo esc_attr( $slug ); ?>][license_status]"
									id="<?php echo esc_attr( $slug ); ?>_license_status"
									value="<?php echo esc_attr( $license_status ); ?>"
								/>
							</li>
						</ul>
					</div>
					<div class="column-license-key">
						<label for="<?php echo esc_attr( $slug ); ?>_license_key">
							<?php echo esc_attr( __( 'License Key', 'slswcclient' ) ); ?>
						</label>							
						<input type="text"
							name="licenses[<?php echo esc_attr( $slug ); ?>][license_key]"
							id="<?php echo esc_attr( $slug ); ?>_license_key"
							value="<?php echo esc_attr( $license_key ); ?>"
							class="input-text regular-text"
						/>
						
						<label for="<?php echo esc_attr( $slug ); ?>_force-client-environment">
							<input type="checkbox"
								name="<?php echo esc_attr( $slug ); ?>_force-client-environment"
								id="<?php echo esc_attr( $slug ); ?>_force-client-environment"
								class="input-checkbox force-client-environment"
								data-slug="<?php echo esc_attr( $slug ); ?>"
								value="0"
							/>
							<?php esc_attr_e( 'Force client environment' ); ?>
						</label>
						
						<label for="<?php echo esc_attr( $slug ); ?>_environment" id="<?php echo esc_attr( $slug ); ?>_environment-label" class="hidden">
							<?php esc_attr_e( 'Environment', 'slswcclient' ); ?>
						</label>

						<select id="<?php echo esc_attr( $slug ); ?>_environment"
							name="licenses[<?php echo esc_attr( $slug ); ?>][environment]"
							class="input-select <?php echo esc_attr( $slug ); ?>_environment hidden"
							data-slug="<?php echo esc_attr( $slug ); ?>"
						>
							<option value="" <?php selected( $license_environment, '' ); ?>><?php esc_attr_e( 'Select environment', 'slswcclient' ); ?></option>
							<option value="staging" <?php selected( $license_environment, 'staging'); ?>><?php esc_attr_e( 'Staging' ); ?></option>
							<option value="live" <?php selected( $license_environment, 'live' ); ?>><?php esc_attr_e( 'Live' ); ?></option>
						</select>


						<input type="hidden"
							name="licenses[<?php echo esc_attr( $slug ); ?>][current_version]"
							id="<?php echo esc_attr( $slug ); ?>_current_version"
							value="<?php echo esc_attr( $current_version ); ?>"
						/>
					</div>
				</div>
				<div class="plugin-card-bottom slswc-plugin-card-bottom">					
					<div class="column-updated">
						<?php if ( $license_expires != '' ): ?>
							<?php esc_attr_e( 'License expires in ', 'slswcclient' ); ?>
							<?php echo esc_attr( human_time_diff( strtotime( $license_expires ) ) ); ?>								
							<?php echo wc_help_tip( $license_expires ); ?>
							<input
								type="hidden"
								name="licenses[<?php echo esc_attr( $slug ); ?>][license_expires]"
								id="<?php echo esc_attr( $slug ); ?>_license_expires"
								value="<?php echo esc_attr(  $license_expires ); ?>"
							/>
						<?php endif; ?>
					</div>
					<div class="column-compatibility">
						<a
							id="<?php echo esc_attr( $slug ); ?>-license-action"
							href="#"
							data-action="<?php echo ( $is_active ? 'deactivate' : 'activate' ); ?>"							
							data-slug="<?php echo esc_attr( $slug ); ?>"
							data-license_key="<?php echo esc_attr( $license_key ); ?>"
							data-version="<?php echo esc_attr( $current_version ); ?>"
							data-domain="<?php echo esc_url_raw( get_site_url('') ); ?>"
							data-nonce="<?php echo esc_attr( wp_create_nonce( 'activate-license-' . esc_attr( $slug ) ) ); ?>"
							data-environment="<?php echo esc_attr( $license_environment ); ?>"
							class='button button-primary license-action'>							
							<?php echo esc_attr( $is_active ? __( 'Deactivate', 'slswcclient' ) : __( 'Activate', 'slswcclient' ) ); ?>
						</a>
					</div>
				</div>
			</div>				
			<?php do_action( 'slswc_after_license_row', $product ); ?>
			<?php
		endforeach;
	}

	/**
	 * Output a list of products.
	 *
	 * @param   string $products The list of products.
	 * @return  void
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public static function list_products( $products ) {
		$products = is_array( $products ) ? $products : (array) $products;

		$type = self::get_tab();

		if ( in_array( $type, array( 'plugins', 'themes' ), true ) ) {
			$slugs    = array();
			$licenses = array();
			foreach ( $products as $slug => $details ) {
				$slugs[]           = $slug;
				$licenses[ $slug ] = $details;
			}
			$args            = array( 'post_name__in' => $slugs );
			$remote_products = (array) self::get_remote_products( $type, $args );
		} else {
			$remote_products = array();
		}

		Helper::log( 'Local products.' );
		Helper::log( $products );
		Helper::log( 'Remote Products' );
		Helper::log( $remote_products );

		?>
		<?php if ( ! empty( $products ) && count( $products ) > 0 ) : ?>
			<h2 class="screen-reader-text"><?php echo esc_attr( __( 'Plugins List', 'slswcclient' ) ); ?></h2>
			<div id="the-list">
				<?php foreach ( $products as $product ) : ?>
					<?php

					$product = is_array( $product ) ? $product : (array) $product;

					if ( array_key_exists( $product['slug'], $remote_products ) ) {
						$product = Helper::recursive_parse_args( (array) $remote_products[ $product['slug'] ], $product );
					}

					$installed = file_exists( $product['file'] ) || is_dir( $product['file'] ) ? true : false;

					$name_version = esc_attr( $product['name'] ) . ' ' . esc_attr( $product['version'] );
					$action_class = $installed ? 'update' : 'install';
					$action_label = $installed ? __( 'Update Now', 'slswcclient' ) : __( 'Install Now', 'slswcclient' );

					do_action( 'slswc_before_products_list', $products );

					$thumb_class = 'theme' === $product['type'] ? 'appearance' : 'plugins';
					?>
				<div class="plugin-card plugin-card-<?php echo esc_attr( $product['slug'] ); ?>">
					<div class="plugin-card-top">
						<div class="name column-name">
							<h3>
								<a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $product['slug'] . '&section=changelog&TB_iframe=true&width=600&height=800' ) ); ?>"
									class="thickbox open-plugin-details-modal">
									<?php echo esc_attr( $product['name'] ); ?>
									<?php if ( '' === $product['thumbnail'] ) : ?>
										<i class="dashicons dashicons-admin-<?php echo esc_attr( $thumb_class ); ?> plugin-icon slswc-product-thumbnail"></i>
									<?php else : ?>
										<img src="<?php echo esc_attr( $product['thumbnail'] ); ?>" class="plugin-icon" alt="<?php echo esc_attr( $name_version ); ?>">
									<?php endif; ?>
								</a>
							</h3>
						</div>
						<div class="action-links">
							<ul class="plugin-action-buttons">
								<li>
									<?php if ( empty( $product['download_url'] ) ) : ?>
										<?php esc_attr_e( 'Manual Download Only.', 'slswcclient' ); ?>
									<?php else : ?>
									<a class="slswc-<?php echo esc_attr( $action_class ); ?>-now <?php echo esc_attr( $action_class ); ?>-now button aria-button-if-js"
										data-download_url="<?php echo esc_url_raw( $product['download_url'] ); ?>"
										data-slug="<?php echo esc_attr( $product['slug'] ); ?>"
										href="#"
										<?php // translators: %s - The license name and version. ?>
										aria-label="<?php echo esc_attr( sprintf( __( 'Update %s now', 'slswcclient' ), esc_attr( $name_version ) ) ); ?>"
										data-name="<?php echo esc_attr( $name_version ); ?>"
										data-nonce="<?php echo esc_attr( wp_create_nonce( 'slswc_client_install_' . $product['slug'] ) ); ?>"
										role="button"
										data-type="<?php echo esc_attr( $product['type'] ); ?>">
										<?php echo esc_attr( $action_label ); ?>
									</a>
									<?php endif; ?>
								</li>
								<li>
									<a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $product['slug'] . '&section=changelog&TB_iframe=true&width=772&height=840' ) ); ?>"
										class="thickbox open-plugin-details-modal"
										<?php // translators: %s - Product name. ?>
										aria-label="<?php echo esc_attr( sprintf( __( 'More information about %s', 'slswcclient' ), esc_attr( $name_version ) ) ); ?>"
										data-title="<?php echo esc_attr( $name_version ); ?>">
										<?php echo esc_attr( __( 'More Details', 'slswcclient' ) ); ?>
									</a>
								</li>
							</ul>
						</div>
						<div class="desc column-description">
							<p><?php echo esc_attr( substr( $product['description'], 0, 110 ) ); ?></p>
							<p class="authors"> <cite>By <a href="<?php echo esc_attr( $product['author_uri'] ); ?>"><?php echo esc_attr( $product['author'] ); ?></a></cite></p>
						</div>
					</div>
					<div class="plugin-card-bottom slswc-plugin-card-bottom">					
						<div class="column-updated">
							<strong>Last Updated: </strong>
							<?php echo esc_attr( human_time_diff( strtotime( $product['updated'] ) ) ); ?> ago.
						</div>
						<div class="column-compatibility">
							<?php self::show_compatible( $product['compatible_to'] ); ?>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
				<?php do_action( 'slswc_after_list_products', $products ); ?>
			</div>
		<?php else : ?>
			<div class="no-products">
				<p><?php esc_attr_e( 'No products in this category yet.', 'slswcclient' ); ?></p>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Output API Settings form
	 *
	 * @return void
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public static function api_form() {
		$keys = Helper::get_api_keys();
		?>
		<h2><?php esc_attr_e( 'Downloads API Settings', 'slswcclient' ); ?></h2>
		<?php if ( empty( $keys ) && ! Helper::is_connected() ) : ?>
			<?php
			$username        = isset( $keys['username'] ) ? $keys['username'] : '';
			$consumer_key    = isset( $keys['consumer_key'] ) ? $keys['consumer_key'] : '';
			$consumer_secret = isset( $keys['consumer_secret'] ) ? $keys['consumer_secret'] : '';
			?>
		<p>
			<?php esc_attr_e( 'The Downloads API allows you to install plugins directly from the Updates Server into your website instead of downloading and uploading manually.', 'slswcclient' ); ?>
		</p>
		<p class="about-text">
			<?php esc_attr_e( 'Enter API details then save to proceed to the next step to connect', 'slswcclient' ); ?>
		</p>
		<form name="api-keys" method="post" action="">
			<?php wp_nonce_field( 'save_api_keys', 'save_api_keys_nonce' ); ?>
			<input type="hidden" name="save_api_keys_check" value="1" />
			<table class="form-table">
				<tbody>
					<tr>
						<th><?php esc_attr_e( 'Username', 'slswcclient' ); ?></th>
						<td>
							<input type="text"
									name="username"
									value="<?php echo esc_attr( $username ); ?>"
							/>
						</td>
					</tr>
					<tr>
						<th><?php esc_attr_e( 'Consumer Key', 'slswcclient' ); ?></th>
						<td>
							<input type="password"
									name="consumer_key"
									value="<?php echo esc_attr( $consumer_key ); ?>"
							/>
						</td>
					</tr>
					<tr>
						<th><?php esc_attr_e( 'Consumer Secret', '' ); ?></th>
						<td>
							<input type="password"
									name="consumer_secret"
									value="<?php echo esc_attr( $consumer_secret ); ?>"
							/>
						</td>
					</tr>
					<tfoot>
						<tr>
							<th></th>
							<td>
								<input type="submit"
										id="save-api-keys"
										class="button button-primary"
										value="Save API Keys"
								/>
							</td>
						</tr>
					</tfoot>
				</tbody>
			</table>
		</form>
		<?php elseif ( ! empty( $keys ) && ! Helper::is_connected() ) : ?>
			<form name="connect" method="post" action="">
				<?php wp_nonce_field( 'connect', 'connect_nonce' ); ?>
				<p><?php esc_attr_e( 'Click on the button to connect your account now.', 'slswcclient' ); ?></p>
				<input type="submit"
						id="connect"
						class="button button-primary"
						value="<?php esc_attr_e( 'Connect Account Now', 'slswcclient' ); ?>"
				/>
			</form>

			<form name="reset_api_settings" method="post" action="">
				<?php wp_nonce_field( 'reset_api_settings', 'reset_api_settings_nonce' ); ?>
				<p></p>
				<input type="submit"
						id="reset_api_settings"
						class="button"
						value="<?php esc_attr_e( 'Reset API Keys', 'slswcclient' ); ?>"
				/>
			</form>

		<?php else : ?>
			<p><?php esc_attr_e( 'Your account is connected.', 'slswcclient' ); ?></p>
			<p><?php esc_attr_e( 'You should be able to see a list of your purchased products and get convenient automatic updates.', 'slswcclient' ); ?></p>
			<form name="disconnect" method="post" action="">
				<?php wp_nonce_field( 'disconnect', 'disconnect_nonce' ); ?>
				<input type="submit"
						id="disconnect"
						class="button button-primary"
						value="<?php esc_attr_e( 'Disconnect', 'slswcclient' ); ?>"
				/>
			</form>
		<?php endif; ?>
		<?php
	}

	/**
	 * Output the product ratings
	 *
	 * @return void
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param array $args The options for the rating.
	 */
	public static function output_ratings( $args ) {
		wp_star_rating( $args );
		?>
		<span class="num-ratings" aria-hidden="true">(<?php echo esc_attr( $args['number'] ); ?>)</span>
		<?php
	}

	/**
	 * Show compatibility message
	 *
	 * @param   string $version - The version to compare with installed WordPress version.
	 * @return  void
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public static function show_compatible( $version ) {
		global $wp_version;
		$compatible = version_compare( $version, $wp_version ) >= 0 ? true : false;

		if ( $compatible ) {
			$compatibility_label = __( 'Compatible', 'slswcclient' );
			$compatibility_class = 'compatible';
		} else {
			$compatibility_label = __( 'Not compatible', 'slswcclient' );
			$compatibility_class = 'incompatible';
		}
		?>
		<span class="compatibility-<?php echo esc_attr( $compatibility_class ); ?>">
			<strong><?php echo esc_attr( $compatibility_label ); ?></strong>
			<?php
			esc_attr_e( ' with your version of WordPress', 'slswcclient' );
			?>
		</span>
		<?php
	}

	/**
	 * License activated field.
	 *
	 * @since 1.0.0
	 * @since 1.0.1
	 * @version 1.0.0
	 *
	 * @param string $status The license status.
	 */
	public static function license_status_field( $status ) {

		$license_labels = Helper::license_status_types();

		echo empty( $status ) ? '' : esc_attr( $license_labels[ $status ] );
	}

	

	/**
	 * Connect to the api server using API keys
	 *
	 * @return  array
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public static function connect() {
		$keys       = Helper::get_api_keys();
		$connection = helper::server_request( self::$license_server_url, 'connect', $keys );

		Helper::log( 'Connecting...' );

		if ( $connection && $connection->connected && 'ok' === $connection->status ) {
			update_option( 'slswc_api_connected', apply_filters( 'slswc_api_connected', 'yes' ) );
			update_option( 'slswc_api_auth_user', apply_filters( 'slswc_api_auth_user', $connection->auth_user ) );

			return true;
		}

		return false;
	}

	/**
	 * Get more details about the product from the license server.
	 *
	 * @param   string $slug The software slug.
	 * @param   string $type The type of software. Expects plugin/theme, default 'plugin'.
	 * @return  array
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public static function get_remote_product( $slug = '', $type = 'plugin' ) {

		$request_info = array(
			'slug' => empty( $slug ) ? self::$slug : $slug,
			'type' => $type,
		);

		$license_data = get_option( $slug . '_license_manager', null );

		if ( Helper::is_connected() ) {
			$request_info = array_merge( $request_info, Helper::get_api_keys() );
		} elseif ( null !== $license_data && ! empty( $license_data['license_key'] ) ) {
			$request_info['license_key'] = trim( $license_data['license_key'] );
		}

		$response = Helper::server_request( self::$license_server_url, 'product', $request_info );

		if ( is_object( $response ) && 'ok' === $response->status ) {
			return $response->product;
		}

		Helper::log( 'Get remote product' );
		Helper::log( $response->product );

		return array();
	}

	/**
	 * Get a user's purchased products.
	 *
	 * @param   string $type The type of products. Expects plugins|themes, default 'plugins'.
	 * @param   array  $args The arguments to form the query to search for the products.
	 * @return  array
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public static function get_remote_products( $type = 'plugins', $args = array() ) {
		$licensed_products = array();
		$request_info      = array();
		$slugs             = array();

		$request_info['type'] = $type;

		$licenses_data = self::get_license_data_for_all( $type );

		foreach ( $licenses_data as $slug => $_license_data ) {
			if ( ! self::ignore_status( $_license_data['license_status'] ) ) {
				$_license_data['domain']    = untrailingslashit( str_ireplace( array( 'http://', 'https://' ), '', home_url() ) );
				$_license_data['slug']      = $slug;
				$slugs[]                    = $slug;
				$licensed_products[ $slug ] = $_license_data;
			}
		}

		if ( ! empty( $licensed_products ) ) {
			$request_info['licensed_products'] = $licensed_products;
		}

		$request_info['query_args'] = wp_parse_args(
			$args,
			array(
				'post_name__in' => $slugs,
			)
		);

		if ( Helper::is_connected() ) {
			$request_info['api_keys'] = Helper::get_api_keys();
		}

		$response = Helper::server_request( self::$license_server_url, 'products', $request_info );

		Helper::log( 'Getting remote products' );
		Helper::log( $response );

		if ( is_object( $response ) && 'ok' === $response->status ) {
			return $response->products;
		}

		return array();
	}

	/**
	 * Get license data for all locally installed
	 *
	 * @param   string $type The type of products to return license details for. Expects `plugins` or `themes`, default empty.
	 * @return  array
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public static function get_license_data_for_all( $type = '' ) {
		global $slswc_updater;
		$all_products  = array();
		$licenses_data = array();

		if ( self::valid_type( $type ) ) {
			$function              = "get_local_{$type}";
			$all_products[ $type ] = $slswc_updater->$function();
		} else {
			$all_products['themes']  = $slswc_updater->get_local_themes();
			$all_products['plugins'] = $slswc_updater->get_local_plugins();
		}

		foreach ( $all_products as $type => $_products ) {

			foreach ( $_products as $slug => $_product ) {
				$_license_data          = get_option( $slug . '_license_manager', array() );
				$licenses_data[ $slug ] = $_license_data;
			}
		}

		$maybe_type_key = '' !== $type ? $type : '';
		return apply_filters( 'slswc_client_license_data_for_all' . $maybe_type_key, $licenses_data );
	}

	/**
	 * Check if valid product type.
	 *
	 * @param   string $type The plural product type plugins|themes.
	 * @return  bool
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public static function valid_type( $type ) {
		return in_array( $type, array( 'themes', 'plugins' ), true );
	}

	/**
	 * Check if status should be ignored
	 *
	 * @param   string $status The status tp check.
	 * @return  bool
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public static function ignore_status( $status ) {
		$ignored_statuses = array( 'expired', 'max_activations', 'failed' );
		return in_array( $status, $ignored_statuses, true );
	}

	/**
	 * Get the current tab.
	 *
	 * @return  string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public static function get_tab() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['tab'] ) && ! empty( $_GET['tab'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) : 'licenses';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	

	

	/**
	 * Save a list of products to the database.
	 *
	 * @param array $products List of products to save.
	 * @return void
	 */
	public static function save_products( $products = array() ) {
		if ( empty( $products ) ) {
			$products = self::$products;
		}
		Helper::log( 'Saving products...' );
		Helper::log( $products );
		update_option( 'slswc_products', $products );
	}

	/**
	 * Recursively merge two arrays.
	 *
	 * @param array $args User defined args.
	 * @param array $defaults Default args.
	 * @return array $new_args The two array merged into one.
	 */
	public static function recursive_parse_args( $args, $defaults ) {
		$args     = (array) $args;
		$new_args = (array) $defaults;
		foreach ( $args as $key => $value ) {
			if ( is_array( $value ) && isset( $new_args[ $key ] ) ) {
				$new_args[ $key ] = Helper::recursive_parse_args( $value, $new_args[ $key ] );
			} else {
				$new_args[ $key ] = $value;
			}
		}
		return $new_args;
	}

	/**
	 * Get all messages to be added to admin notices.
	 *
	 * @return array
	 * @version 1.0.2
	 * @since   1.0.2
	 */
	public static function get_messages() {
		return self::$messages;
	}

	/**
	 * Add a message to be shown to admin
	 *
	 * @param string $key     The array key of the message.
	 * @param string $message The message to be added.
	 * @param string $type    The type of message to be added.
	 * @return void
	 * @version 1.0.2
	 * @since   1.0.2
	 */
	public static function add_message( $key, $message, $type = 'success' ) {
		self::$messages[] = array(
			'key'     => $key,
			'message' => $message,
			'type'    => $type,
		);
	}

	/**
	 * Display license update messages.
	 *
	 * @return void
	 * @version 1.0.2
	 * @since   1.0.2
	 */
	public static function display_messages() {
		if ( empty( self::$messages ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		foreach ( self::$messages as $message ) {
			echo sprintf( '<div class="%1$s notice is-dismissible"><p>%2$s</p></div>', esc_attr( $message['type'] ), wp_kses_post( $message['message'] ) );
		}
	}
}