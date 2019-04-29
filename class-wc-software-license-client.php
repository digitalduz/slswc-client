<?php
/**
 * The WC Software License Client Library
 *
 * This class defines all code necessary to check for a valid license and available updates stored on your WooCommerce Software License Server
 *
 * Documentation can be found here : http://docs.wcvendors.com/wc-software-license-server
 *
 * To integrate this into your software product include the following code in your MAIN plugin file, do not attempt
 * to add this code in any other file but your main plugin file.
 *
 *      // Required Parameters
 *
 *       @param string  required $license_server_url - The base url to your woocommerce shop
 *       @param string  required $version - the software version currently running
 *       @param string  required $text_domain - the text domain of the plugin - do we need this?
 *       @param string  required $base_file - path to the plugin file or directory, relative to the plugins directory
 *       @param string  required $name - A nice name for the plugin for use in messages
 *
 *       // Optional Parameters
 *       @param string optional $slug - the plugin slug if your class file name is different to the specified slug on the WooCommerce Product
 *       @param integer optional $update_interval - time in hours between update checks
 *       @param bool optional $debug - enable debugging in the client library.
 *
 *  require_once plugin_dir_path( __FILE__ ) . 'path/to/wc-software-license-client/class-wc-software-license-client.php';
 *
 *  function wcslc_instance(){
 *      return WC_Software_License_Client::get_instance( 'http://yourshopurl.here.com', 1.0.0 );
 *  } // wcslc_instance()
 *
 *  wcslc_instance();
 *
 * @version     1.0.2
 * @since       1.0.0
 * @package     WC_Software_License_Client
 * @author      Jamie Madden <support@wcvendors.com>
 * @link        http://www.wcvendors.com/wc-software-license-server
 * @todo        Need to cache results and updates to reduce load
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Software_License_Client_Manager' ) ) :
	/**
	 * Class to manage products relying on the Software License Server for WooCommerce.
	 *
	 * @since   1.0.4
	 * @version 1.0.4
	 */
	class WC_Software_License_Client_Manager {
		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		private static $instance = null;

		/**
		 * Version - current plugin version.
		 *
		 * @since 1.0.2
		 */
		public $version;

		/**
		 * License URL - The base URL for your woocommerce install.
		 *
		 * @since 1.0.2
		 */
		public $license_server_url;

		/**
		 * The plugin slug to check for updates with the server.
		 *
		 * @since 1.0.2
		 */
		public $slug;

		/**
		 * Plugin text domain.
		 *
		 * @since 1.0.2
		 */
		public $text_domain;

		/**
		 * List of products
		 *
		 * @var     array
		 * @since   1.0.4
		 * @version 1.0.4
		 */
		public $products;

		/**
		 * Don't allow cloning
		 *
		 * @since 1.0.2
		 */
		private function __clone() {}

		/**
		 * Don't allow unserializing instances of this class
		 *
		 * @since 1.0.2
		 */
		private function __wakeup() {}

		/**
		 * Return instance of this class
		 *
		 * @param   string $license_server_url
		 * @return  object
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public static function get_instance( $license_server_url, $slug, $text_domain ) {

			if ( null === self::$instance ) {
				self::$instance = new self( $license_server_url, $slug, $text_domain );
			}

			return self::$instance;

		} // get_instance()

		/**
		 * Initialize the class actions
		 *
		 * @since 1.0.2
		 * @version 1.0.2
		 * @param string $license_server_url - The base url to your woocommerce shop
		 * @param string $products - The list of locally installed products
		 */
		private function __construct( $license_server_url, $slug, $text_domain ) {
			$this->license_server_url = $license_server_url;
			$this->slug               = $slug;
			$this->text_domain        = $text_domain;

			$this->delete_products();

			$this->products = $this->get_local_products();

			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'wp_ajax_slswc_install_product', array( $this, 'product_background_installer' ) );
			
			if ( $this->is_products_page() ) {
				add_action( 'admin_footer', array( $this, 'admin_footer_script' ) );
			}
		}

		/**
		 * Check if the current page is a product list page.
		 *
		 * @return  boolean
		 * @version 1.0.2
		 * @since   1.0.2
		 */
		public function is_products_page() {
			$tabs    = array( 'plugins', 'themes' );
			$page    = 'slswc_license_manager';
			$is_page = isset( $_GET['page'] ) && $page === $_GET['page'] ? true : false;
			$is_tab  = isset( $_GET['tab'] ) && in_array( wp_unslash( $_GET['tab'] ), $tabs, true ) ? true : false;
			
			if ( is_admin() && $is_page && $is_tab ) {
				return true;
			}

			return false;
		}

		/**
		 * Add script to admin footer.
		 *
		 * @return  void
		 * @version 1.0.2
		 * @since   1.0.2
		 */
		public function admin_footer_script() { ?>
		<script type="text/javascript">
			jQuery( function( $ ){
				$('.slswc-install-now').on( 'click', function(e){
					e.preventDefault();
					
					var $el = $(this);
					var package = $(this).data('package');
					var name    = $(this).data('name');
					var slug    = $(this).data('slug');
					var type    = $(this).data('type');
					
					var label   = $(this).html();

					var action_label= "<?php esc_attr_e( 'Processing', $this->text_domain ); ?>";
					
					$(this).html('<img src="<?php echo esc_url( admin_url( 'images/loading.gif' ) ); ?>" /> ' + action_label );
					
					$.ajax({
						url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
						data: {
							action:  'slswc_install_product',
							package: package,
							name:    name,
							slug:    slug,
							type:    type === '' ? 'plugin': 'theme',
							nonce:   '<?php echo esc_attr( wp_create_nonce( 'slswc_client_install_product' ) ); ?>'
						},
						dataType: 'json',
						type: 'POST',
						success: function( data ) {
							$el.html( 'Done' );
							$el.attr('disabled', 'disabled');
						},
						error: function( data ) {
							alert( data.responseJSON.message );
						}
					});
				});
			} );
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
		 * @since   1.0.2
		 * @version 1.0.2
		 * @access  public
		 */
		public function add_admin_menu() {

			$page = add_options_page(
				__( 'License Manager', $this->text_domain ),
				__( 'License Manager', $this->text_domain ),
				'manage_options',
				'slswc_license_manager',
				array( $this, 'show_installed_products' )
			);
		}

		/**
		 * List all products installed on this server.
		 *
		 * @return  void
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function show_installed_products() {
			$user_products = array();
			
			if ( ! empty( $this->get_api_keys() ) && $this->is_connected() ) {
				$user_products = (array) $this->get_my_products();
			}
			
			$local_products = $this->get_local_products();

			if ( ! empty( $user_products ) && ! empty( $local_products ) ) {
				foreach ( $user_products as $key => $products ) {

					$merged_products = array();
					foreach ( $products as $slug => $details ) {
						$merged_products[ $slug ] = array_merge( (array) $details, (array) $local_products[ $slug ] );
					}

					$this->products[ $key ] = $merged_products;
				}
			}

			$products = apply_filters( 'slswc_products', $this->products );

			$license_admin_url = admin_url( 'admin.php?page=slswc_license_manager' );
			$tab               = isset( $_GET['tab'] ) && ! empty( $_GET['tab'] ) ? esc_attr( $_GET['tab'] ) : '';
			?>
			<div class="wrap">
				<div class="notice update">
					<?php echo __( 'Please Note: If your license is active on another website you will need to deactivate this in your wcvendors.com my downloads page before being able to activate it on this site.  IMPORTANT:  If this is a development or a staging site dont activate your license.  Your license should ONLY be activated on the LIVE WEBSITE you use Pro on.', $this->text_domain ); ?>
				</div>
				<h1><?php _e( 'Licensed Plugins and Themes.', $this->text_domain ); ?></h1>
				<?php

				if ( isset( $_POST['save_api_keys_check'] ) 
					&& ! empty( esc_attr( $_POST['save_api_keys_check'] ) )
					&& wp_verify_nonce( esc_attr( $_POST['save_api_keys_nonce'] ), 'save_api_keys' )
					) {
					$save_username        = update_option( 'slswc_api_username', esc_attr( $_POST['username'] ) );
					$save_consumer_key    = update_option( 'slswc_consumer_key', esc_attr( $_POST['consumer_key'] ) );
					$save_consumer_secret = update_option( 'slswc_consumer_secret', esc_attr( $_POST['consumer_secret'] ) );

					if ( $save_username && $save_consumer_key && $save_consumer_secret ) {
						?>
						<p class="updated">API Settings saved</p>
						<?php
					}
				}

				if ( ! empty( $_POST['connect_nonce'] ) && wp_verify_nonce( $_POST['connect_nonce'], 'connect' ) ) {
					$connect = $this->connect();
				}

				if ( ! empty( $_POST['disconnect_nonce'] ) && wp_verify_nonce( $_POST['disconnect_nonce'], 'disconnect' ) ) {
					update_option( slswc()->prefix . 'api_connected', 'no' );
				}

				?>
				<h2 class="nav-tab-wrapper">
					<a href="<?php echo $license_admin_url; ?>&tab=licenses"
						class="nav-tab <?php echo ( $tab == 'licenses' || empty( $tab ) ) ? 'nav-tab-active' : ''; ?>">
						<?php _e( 'Licenses' ); ?>
					</a>
					<a href="<?php echo $license_admin_url; ?>&tab=plugins"
						class="nav-tab <?php echo ( $tab == 'plugins' ) ? 'nav-tab-active' : ''; ?>">
						<?php _e( 'Plugins', $this->text_domain ); ?>
					</a>
					<a href="<?php echo $license_admin_url; ?>&tab=themes"
						class="nav-tab <?php echo ( $tab == 'themes' ) ? 'nav-tab-active' : ''; ?>">
						<?php _e( 'Themes', $this->text_domain ); ?>
					</a>
					<a href="<?php echo $license_admin_url; ?>&tab=api"
						class="nav-tab <?php echo ( $tab == 'api' ) ? 'nav-tab-active' : ''; ?>">
						<?php _e( 'API' ); ?>
					</a>
				</h2>

				<?php if ( 'licenses' === $tab || empty( $tab ) ) : ?>
				<div id="licenses">
					<?php $this->licenses_form( $products ); ?>
				</div>
				<?php elseif ( 'plugins' === $tab ) : ?>
				<div id="plugins">
					<?php $this->list_products( $products['plugins'] ); ?>
				</div>

				<?php elseif ( 'themes' === $tab ) : ?>
				<div id="themes">			
					<?php $this->list_products( $products['themes'] ); ?>
				</div>	
				<?php else : ?>
				<div id="api">
					<?php $this->api_form(); ?>
				</div>

			</div>
			<?php
			endif;
		}

		/**
		 * Output licenses form
		 *
		 * @param  array $products
		 * @return void
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function licenses_form( $products ) {
			?>
			<style>
			.licenses-table{margin-top: 9px;}
			.licenses-table th, .licenses-table td {padding: 8px 10px;}
			.licenses-table .actions {vertical-align: middle;width: 20px;}
			.licenses-table .license-field input[type="text"], .licenses-table .license-field select{
				width: 100% !important;
			}
			</style>
			<?php

			if ( ! empty( $_POST['save_licenses_nonce'] ) && wp_verify_nonce( $_POST['save_licenses_nonce'], 'save_licenses' ) ) {

				foreach ( $_POST['licenses'] as $slug => $license_details ) {
					$license_details = wp_parse_args(
						$license_details,
						array(
							'license_status'  => 'inactive',
							'license_key'     => '',
							'license_expires' => '',
							'current_version' => $this->version,
							'environment'     => 0,
						)
					);
					update_option( $slug . '_license_manager', $license_details );
					do_action( "slswc_save_license_{$slug}", $license_details );
				}
			}
			?>
			<form name="licenses-form" action="" method="post">
				<?php wp_nonce_field( 'save_licenses', 'save_licenses_nonce' ); ?>
				<table class="form-table licenses-table widefat" >
					<thead>
						<tr>
							<th><?php _e( 'Product Name', $this->text_domain ); ?></th>
							<th><?php _e( 'License Key', $this->text_domain ); ?></th>
							<th><?php _e( 'License Status', $this->text_domain ); ?></th>
							<th><?php _e( 'License Expires', $this->text_domain ); ?></th>
							<th><?php _e( 'Deactivate', $this->text_domain ); ?></th>
							<th><?php _e( 'Environment', $this->text_domain ); ?></th>
							<?php do_action( 'slswc_after_licenses_column_headings' ); ?>
							<!--<th><?php _e( 'Action', $this->text_domain ); ?></th>-->
						</tr>
					</thead>
					<tbody>
						<?php
						if ( ! empty( $products['plugins'] ) ) :
							$this->licenses_rows( $products['plugins'] );
							do_action( 'slswc_after_plugins_licenses_list' );
							endif;

						if ( ! empty( $products['themes'] ) ) :
							$this->licenses_rows( $products['themes'] );
							do_action( 'slswc_after_themes_licenses_list' );
							endif;
						?>
						<?php do_action( 'slswc_after_products_licenses_list', $products ); ?>
					</tbody>
				</table>	
				<p>
					<?php submit_button( __( 'Save Licenses', 'primary' ), 'save_licenses' ); ?>
				</p>
			</form>
			<?php
		}

		/**
		 * Licenses rows output
		 *
		 * @param   array $products
		 * @return  void
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function licenses_rows( $products ) {

			foreach ( $products as $product ) :
				$option_name  = $product['slug'] . '_license_manager';
				$license_info = get_option( $option_name );
				$product_name = ! empty( $product['name'] ) ? $product['name'] : $product['title'];
				$slug         = esc_attr( $product['slug'] );
				$action       = ! empty( $license_info['deactivate_license'] ) ? $license_info['deactivate_license'] : $license_info['activate_license'];
				?>
				<tr>
					<td><?php echo esc_attr( $product_name ); ?></td>
					<td class="license-field">
						<input type="text"
								name="licenses[<?php echo $slug; ?>][license_key]"
								id="<?php echo $slug; ?>_license_key"
								value="<?php echo esc_attr( $license_info['license_key'] ); ?>"
						/>
					</td>
					<td class="license-field">
						<?php $this->license_status_field( $license_info['license_status'] ); ?>
						<input type="hidden"
							   name="licenses[<?php echo $slug; ?>][license_status]"
							   id="<?php echo $slug; ?>_license_status"
							   value="<?php echo esc_attr( $license_info['license_status'] ); ?>"
						/>
					</td>
					<td class="license-field">
						<?php _e( $license_info['license_expires'] ); ?>
						<input type="hidden"
							   name="licenses[<?php echo $slug; ?>][license_expires]"
							   id="<?php echo $slug; ?>_license_expires"
							   value="<?php echo esc_attr( $license_info['license_expires'] ); ?>"
						/>
					</td>
					<td class="license-field">
						<input type="checkbox"
								name="licenses[<?php echo $slug; ?>][deactivate_license]"
								id="<?php echo $slug; ?>[deactivate_license]"
								value="1"
								<?php checked( 1, $action ); ?>
						/>
					</td>
					<td class="license-field">
						<input type="radio"
								name="licenses[<?php echo $slug; ?>][environment]"
								id="<?php echo $slug; ?>[environment]"
								value="live"
								<?php checked( 'live', $license_info['environment'] ); ?>
						/> <?php _e( 'Live', $this->text_domain ); ?>

						<input type="radio"
								name="licenses[<?php echo $slug; ?>][environment]"
								id="<?php echo $slug; ?>[environment]"
								value="staging"
								<?php checked( 'staging', $license_info['environment'] ); ?>
						/> <?php _e( 'Staging', $this->text_domain ); ?>
					</td>
					<?php do_action( 'slswc_after_license_column', $product ); ?>
					<!--<td>
						<a href="#"><span class="dashicons dashicons-yes"></span> Check</a>
					</td>-->
				</tr>
				<?php do_action( 'slswc_after_license_row', $product ); ?>
			<?php
			endforeach;
		}

		/**
		 * Output a list of products
		 *
		 * @param string $type
		 * @return void
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function list_products( $products ) {

			$products = is_array( $products ) ? $products : (array) $products;
			?>
			<div class="wp-list-table widefat plugin-install">				
				<?php if ( ! empty( $products ) && count( $products ) > 0 ) : ?>
				<div class="plugins-popular-tags-wrapper">
					<h2 class="screen-reader-text"><?php _e( 'Plugins List', $this->text_domain ); ?></h2>
					<div id="the-list">
						<?php foreach ( $products as $product ) : ?>
						<?php

						$product = is_array( $product ) ? $product : (array) $product;

						$installed = file_exists( $product['plugin_file'] ) || is_dir( $product['plugin_file'] ) ? true : false;

						$name_version = esc_attr( $product['name'] ) . ' ' . esc_attr( $product['version'] );
						$action_class = $installed ? 'update' : 'install';
						$action_label = $installed ? __( 'Update Now', $this->text_domain ) : __( 'Install Now' );

						do_action( 'slswc_before_products_list', $products );
						?>
						<div class="plugin-card plugin-card-<?php echo $product['slug']; ?>">
							<div class="plugin-card-top">
								<div class="name column-name">
									<h3>
										<a href="#" class="thickbox open-plugin-details-modal">
											<?php echo esc_attr( $product['name'] ); ?>
											<img src="<?php echo esc_attr( $product['thumbnail'] ); ?>" class="plugin-icon" alt="<?php echo $name_version; ?>">
										</a>
									</h3>
								</div>
								<div class="action-links">
									<ul class="plugin-action-buttons">
										<li>
											<a  class="slswc-<?php echo $action_class; ?>-now button aria-button-if-js"
												data-package="<?php echo $product['download_url']; ?>"
												data-slug="<?php echo $product['slug']; ?>"
												href="<?php echo admin_url( "/update.php?action=upgrade-product&amp;product={$this->slug}&amp;_wpnonce=" . wp_create_nonce( 'install_product' ) ); ?>"
												aria-label="<?php echo sprintf( __( 'Update %s now', $this->text_domain ), esc_attr( $name_version ) ); ?>"
												data-name="<?php echo esc_attr( $name_version ); ?>"
												role="button"
												type="<?php echo $product['type']; ?>">
												<?php echo $action_label; ?>
											</a>
										</li>
										<li>
											<a href="#" class="thickbox open-plugin-details-modal"
												aria-label="<?php echo sprintf( __( 'More information about %s', $this->text_domain ), esc_attr( $name_version ) ); ?>"
												data-title="<?php echo esc_attr( $name_version ); ?>">
												<?php _e( 'More Details', $this->text_domain ); ?>
											</a>
										</li>
									</ul>
								</div>
								<div class="desc column-description">
									<p><?php echo $product['description']; ?></p>
									<p class="authors"> <cite>By <a href="<?php echo esc_attr( $product['author_uri'] ); ?>"><?php echo esc_attr( $product['author'] ); ?></a></cite></p>
								</div>
							</div>
							<div class="plugin-card-bottom">							
								<div class="vers column-rating">
									<?php
										$this->output_ratings(
											array(
												'rating' => $product['average_rating'],
												'number' => $product['reviews_count'],
											)
										);
									?>
								</div>
								<div class="column-updated">
									<strong>Last Updated: </strong>
									<?php echo human_time_diff( strtotime( $product['updated'] ), current_time( 'timestamp' ) ); ?> ago.
								</div>
								<div class="column-downloaded">
									<?php printf( __( '%s Active Installations', $this->text_domain ), $product['activations'] ); ?> 
								</div>
								<div class="column-compatibility">
									<span class="compatibility-compatible">
										<?php $this->show_compatible( $product['compatible_to'] ); ?>
									</span>
								</div>
							</div>
						</div>						
						<?php endforeach; ?>
						<?php do_action( 'slswc_after_list_products', $products ); ?>
					</div>
				</div>
				<?php else : ?>
					<div class="no-products">
						<p><?php _e( 'It seems you currently do not have any products yet.', $this->text_domain ); ?></p>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Output API Settings form
		 *
		 * @return void
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function api_form() {
			$keys = $this->get_api_keys();
			?>
			<h2><?php _e( 'API Settings', $this->text_domain ); ?></h2>

			<?php if ( empty( $keys ) && ! $this->is_connected() ) : ?>
			
			<p class="about-text">
				<?php _e( 'Enter your marketplace API details and click save. On the next step click Connect to get your subscriptions listed here.', $this->text_domain ); ?>
			</p>
			
			<form name="api-keys" method="post" action="">
				<?php wp_nonce_field( 'save_api_keys', 'save_api_keys_nonce' ); ?>
				<input type="hidden" name="save_api_keys_check" value="1" />
				<table class="form-table">
					<tbody>
						<tr>
							<th><?php _e( 'Username', '' ); ?></th>
							<td>
								<input type="text"
										name="username"
										value="<?php echo esc_attr( $keys['username'] ); ?>"
								/>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Consumer Key', '' ); ?></th>
							<td>
								<input type="password"
										name="consumer_key"
										value="<?php echo esc_attr( $keys['consumer_key'] ); ?>"
								/>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Consumer Secret', '' ); ?></th>
							<td>
								<input type="password"
										name="consumer_secret"
										value="<?php echo esc_attr( $keys['consumer_secret'] ); ?>"
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
			<?php elseif ( ! empty( $keys ) && ! $this->is_connected() ) : ?>
				<form name="connect" method="post" action="">
					<?php wp_nonce_field( 'connect', 'connect_nonce' ); ?>
					<input type="submit"
							id="connect"
							class="button button-primary"
							value="<?php _e( 'Connect', $this->text_domain ); ?>"
					/>
				</form>
				
			<?php else : ?>
				<p><?php _e( 'Your account is connected.', $this->text_domain ); ?></p>
				<p><?php _e( 'You should be able to see a list of your purchased products and get convenient automatic updates.', $this->text_domain ); ?></p>
				<form name="disconnect" method="post" action="">
					<?php wp_nonce_field( 'disconnect', 'disconnect_nonce' ); ?>
					<input type="submit"
							id="disconnect"
							class="button button-primary"
							value="<?php _e( 'Disconnect', $this->text_domain ); ?>"
					/>
				</form>
			<?php endif; ?>
			<?php
		}

		/**
		 * Output the product ratings
		 *
		 * @return void
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function output_ratings( $args ) {
			wp_star_rating( $args );
			?>
			<span class="num-ratings" aria-hidden="true">(<?php echo esc_attr( $args['number'] ); ?>)</span>
			<?php
		}

		/**
		 * Show compatibility message
		 *
		 * @param   $version - The version to compare with installed WordPress version
		 * @return  void
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function show_compatible( $version ) {
			global $wp_version;
			$compatibility = version_compare( $version, $wp_version, '>' ) ? __( 'Compatible', $this->text_domain ) : __( 'Not compatible', $this->text_domain );
			?>
			<strong><?php echo esc_attr( $compatibility ); ?></strong>
			<?php
			_e( ' with your version of WordPress', $this->text_domain );
		}

		/**
		 * License acivated field
		 *
		 * @since 1.0.0
		 * @since 1.0.1
		 * @access public
		 */
		public function license_status_field( $status ) {

			$license_labels = $this->license_status_types();

			_e( $license_labels[ $status ] );
		}

		/**
		 * The available license status types
		 *
		 * @since   1.0.2
		 * @version 1.0.2
		 * @access  public
		 */
		public function license_status_types() {

			return apply_filters(
				'wcsl_license_status_types',
				array(
					'valid'           => __( 'Valid', $this->text_domain ),
					'deactivated'     => __( 'Deactivated', $this->text_domain ),
					'max_activations' => __( 'Max Activations reached', $this->text_domain ),
					'invalid'         => __( 'Invalid', $this->text_domain ),
					'inactive'        => __( 'Inactive', $this->text_domain ),
					'active'          => __( 'Active', $this->text_domain ),
					'expiring'        => __( 'Expiring', $this->text_domain ),
					'expired'         => __( 'Expired', $this->text_domain ),
				)
			);

		}

		/**
		 * Connect to the api server using API keys
		 *
		 * @return  array
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function connect() {
			$keys       = $this->get_api_keys();
			$connection = $this->server_request( 'connect', $keys );

			if ( $connection->connected && 'ok' === $connection->status ) {
				update_option( 'slswc_api_connected', apply_filters( 'slswc_api_connected', 'yes' ) );
				update_option( 'slswc_api_auth_user', apply_filters( 'slswc_api_auth_user', $connection->auth_user ) );

				$this->products = $connection->products;
				$products = wp_parse_args( $this->get_local_products(), $connection->products );
				
				$this->save_products( $products );

				return true;
			}

			return false;
		}

		/**
		 * Add the product to the list.
		 *
		 * @return  void
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function add_product( $product ) {

			if ( empty( $product ) ) {
				return;
			}

			$type_key = $product['type'] . 's';

			$products = $this->get_local_products();

			if ( array_key_exists( $product['slug'], $products ) ) {
				return;
			}

			$remote_product = $this->get_remote_product( $product['slug'] );
			if ( $remote_product ) {
				$product = array_merge( $product, (array) $remote_product );
			}

			if ( ! array_key_exists( $type_key, $products ) ) {
				$products[ $type_key ] = array();
			}

			if ( ! array_key_exists( $product['slug'], $products[ $type_key ] ) ) {
				$products[ $type_key ][ $product['slug'] ] = $product;

				$this->save_products( $products );
			}
		}

		/**
		 * Get more details about the product from the license server.
		 *
		 * @param   string $slug
		 * @return  array
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function get_remote_product( $slug = '' ) {

			$request_info = array(
				'slug' => empty( $slug ) ? $this->slug : $slug,
			);

			$response = $this->server_request( 'product', $request_info );

			if ( is_object( $response ) && 'ok' === $response->status ) {
				return $response->product;
			}

			return false;
		}

		/**
		 * Get a user's purchased products
		 *
		 * @return  array $products
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function get_my_products() {
			$request_info = array_merge(
				$this->get_api_keys(),
				array()
			);

			$response = $this->server_request( 'myproducts', $request_info );

			if ( is_object( $response ) && 'ok' === $response->status ) {
				return $response->products;
			}

			return false;
		}

		/**
		 * Get all public products
		 *
		 * @return  array
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function get_public_products() {
			$request_info = array_merge(
				$this->get_api_keys()
			);

			$response = $this->server_request( 'products', $request_info );

			if ( is_object( $response ) && 'ok' === $response->status ) {
				return $response->products;
			}

			return false;
		}

		/**
		 * Default product data
		 *
		 * @return  array
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function default_product_details() {
			return array(
				'description'        => '',
				'thumbnail'          => '',
				'author'             => '',
				'required_wp'        => '',
				'compatible_to'      => '',
				'updated'            => '',
				'description'        => '',
				'change_log'         => '',
				'installation'       => '',
				'documentation_link' => '',
				'environment'        => '',
			);
		}

		/**
		 * Get installed products
		 *
		 * @return  array   $products
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function get_local_products( $type = 'plugins' ) {
			$products = get_transient( 'slswc_products' );
			if ( false === $products ) {
				$products = get_option( 'slswc_products' );
				set_transient( 'slswc_products', $products, apply_filters( 'slswc_product_cache_expiry', 12 * HOUR_IN_SECONDS ) );
			}

			if ( empty( $products ) ) {
				$products = array(
					'plugins' => array(),
					'themes'  => array(),
				);
			}

			if ( ! empty( $type ) && ! empty( $products[ $type ] ) ) {
				return $products[ $type ];
			}

			return $products;
		}

		/**
		 * Get the API Keys stored in database
		 *
		 * @return  array
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function get_api_keys() {
			return array_filter(
				array(
					'username'        => get_option( 'slswc_api_username' ),
					'consumer_key'    => get_option( 'slswc_consumer_key' ),
					'consumer_secret' => get_option( 'slswc_consumer_secret' ),
				)
			);
		}

		/**
		 * Delete the products from cache and from database
		 *
		 * @return  void
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function delete_products() {
			delete_option( 'slswc_products' );
			delete_transient( 'slswc_products' );
		}

		/**
		 * Save a list of products to the database.
		 *
		 * @param array $products List of products to save.
		 * @return void
		 */
		public function save_products( $products = array() ) {
			if ( empty( $products ) ) {
				$products = $this->products;
			}
			update_option( 'slswc_products', $products );
			set_transient( 'slswc_products', $products, apply_filters( 'slswc_products_cache_expiry', 12 * HOUR_IN_SECONDS ) );
		}

		/**
		 * Check if the account is connected to the api
		 *
		 * @return  boolean
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function is_connected() {
			$is_connected = get_option( 'slswc_api_connected', 'no' );
			return 'yes' === $is_connected ? true : false;
		}

		/**
		 * ---------------------------------------------------------------------------------
		 * Server Request Functions
		 * ---------------------------------------------------------------------------------
		 */

		/**
		 * Send a request to the server
		 *
		 * @param   $action string activate|deactivate|check_update
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function server_request( $action = 'check_update', $request_info = array() ) {

			// Allow filtering the request info for plugins.
			$request_info = apply_filters( 'wcsl_request_info_' . $this->slug, $request_info );

			// Build the server url api end point fix url build to support the WordPress API.
			$server_request_url = esc_url_raw( $this->license_server_url . 'wp-json/slswc/v1/' . $action . '?' . http_build_query( $request_info ) );

			// Options to parse the wp_safe_remote_get() call.
			$request_options = array( 'timeout' => 30 );

			// Allow filtering the request options.
			$request_options = apply_filters( 'wcsl_request_options_' . $this->slug, $request_options );

			// Query the license server.
			$endpoint_actions = apply_filters( 'slswc_client_post_actions', array( 'connect', 'add_domain', 'remove_domain' ) );
			if ( in_array( $action, $endpoint_actions, true ) ) {
				$response = wp_safe_remote_post( $server_request_url, $request_info );
			} else {
				$response = wp_safe_remote_get( $server_request_url, $request_options );
			}

			// Validate that the response is valid not what the response is.
			$result = $this->validate_response( $response );

			// Check if there is an error and display it if there is one, otherwise process the response.
			if ( ! is_wp_error( $result ) ) {

				$response_body = json_decode( wp_remote_retrieve_body( $response ) );

				// Check the status of the response.
				$continue = $this->check_response_status( $response_body );

				if ( $continue ) {
					return $response_body;
				}
			} else {

				// Display the error message in admin.
				add_settings_error(
					$this->slug . '_license_manager',
					esc_attr( 'settings_updated' ),
					$result->get_error_message(),
					'error'
				);

				// Return null to halt the execution.
				return null;

			}

		} // server_request()

		/**
		 * Validate the license server response to ensure its valid response not what the response is
		 *
		 * @since   1.0.0
		 * @version 1.0.2
		 * @access  public
		 * @param WP_Error | Array The response or WP_Error
		 */
		public function validate_response( $response ) {

			if ( ! empty( $response ) ) {

				// Can't talk to the server at all, output the error.
				if ( is_wp_error( $response ) ) {
					return new WP_Error( $response->get_error_code(), sprintf( __( 'HTTP Error: %s', $this->text_domain ), $response->get_error_message() ) );
				}

				// There was a problem with the initial request
				if ( ! isset( $response['response']['code'] ) ) {
					return new WP_Error( 'wcsl_no_response_code', __( 'wp_safe_remote_get() returned an unexpected result.', $this->text_domain ) );
				}

				// There is a validation error on the server side, output the problem
				if ( $response['response']['code'] == 400 ) {

					$body = json_decode( $response['body'] );

					foreach ( $body->data->params as $param => $message ) {
						return new WP_Error(
							'wcsl_validation_failed',
							sprintf(
								// translators: %s: Error/response message.
								__( 'There was a problem with your license: %s', $this->text_domain ),
								$message
							)
						);
					}
				}

				// The server is broken.
				if ( $response['response']['code'] == 500 ) {
					return new WP_Error(
						'wcsl_internal_server_error',
						sprintf(
							// translators: %s: the http repsonse code from the server.
							__( 'There was a problem with the license server: HTTP response code is : %s', $this->text_domain ),
							$response['response']['code']
						)
					);
				}

				if ( $response['response']['code'] !== 200 ) {
					return new WP_Error(
						'wcsl_unexpected_response_code',
						sprintf(
							__( 'HTTP response code is : % s, expecting ( 200 )', $this->text_domain ),
							$response['response']['code']
						)
					);
				}

				if ( empty( $response['body'] ) ) {
					return new WP_Error(
						'wcsl_no_response',
						__( 'The server returned no response.', $this->text_domain )
					);
				}

				return true;

			}

		} // validate_response()


		/**
		 * Validate the license server response to ensure its valid response not what the response is
		 *
		 * @since   1.0.2
		 * @version 1.0.2
		 * @access  public
		 * @param   object $response_body
		 */
		public function check_response_status( $response_body ) {

			if ( is_object( $response_body ) && ! empty( $response_body ) ) {

				$license_status_types = $this->license_status_types();
				$status               = $response_body->status;

				return ( array_key_exists( $status, $license_status_types ) || 'ok' === $status ) ? true : false;
			}

			return false;

		}  // check_response_status()

		/**
		 * Install a product.
		 *
		 * @param string $slug Product slug.
		 *
		 * @since   1.0.4
		 * @version 1.0.4
		 */
		public static function product_background_installer( $slug='', $package = '' ) {
			// Explicitly clear the event.
			wp_clear_scheduled_hook( 'woocommerce_theme_background_installer', func_get_args() );

			$download_link = esc_url( $_POST['package'] );
			$slug          = esc_attr( $_POST['slug'] );
			$name          = esc_attr( $_POST['name'] );
			$product_type  = esc_attr( $_POST['type'] );

			if ( ! empty( $download_link ) ) {
				// Suppress feedback.
				ob_start();
				
				try {

					require_once ABSPATH . 'wp-admin/includes/file.php';
					include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
					include_once ABSPATH . 'wp-admin/includes/theme.php';
					include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

					WP_Filesystem();

					$skin = new Automatic_Upgrader_Skin();

					if ( 'theme' === $product_type ) {
						$upgrader = new Theme_Upgrader( $skin );
					} else {
						$upgrader = new Plugin_Upgrader( $skin );
					}

					$result = $upgrader->install( $download_link );

					if ( is_wp_error( $result ) ) {
						wp_send_json_error( $result->get_error_message() );
					} elseif ( is_wp_error( $skin->result ) ) {
						wp_send_json_error( array( 'message' => $results->get_error_message() ) );
					} elseif ( is_null( $result ) ) {
						wp_send_json_error(
							array(
								'message' => 'Unable to connect to the filesystem. Please confirm your credentials.'
							)
						);
					}

					wp_send_json_success( array( 'result' => $result ) );
				} catch ( Exception $e ) {
					wp_send_json_error(
						array(
							'slug'    => $slug . '_install_error',
							'message' => sprintf(
								// translators: 1: theme slug, 2: error message, 3: URL to install theme manually.
								__( '%1$s could not be installed (%2$s). <a href="%3$s">Please install it manually by clicking here.</a>', $this->text_domain ),
								$slug,
								$e->getMessage(),
								esc_url( admin_url( 'update.php?action=install-' . $product_type . '&' . $product_type . '=' . $slug . '&_wpnonce=' . wp_create_nonce( 'install-' . $product_type . '_' . $slug ) ) )
							),
						)
					);
				}

				wp_send_json_error( array( 'message' => "Gone all this way" ) );

				// Discard feedback.
				ob_end_clean();
			}

			wp_send_json(
				array(
					'message' => __( 'Failed to install product. Download link not provided or is invalid.', $this->text_domain )
				)
			);
		}
	}
endif;

if ( ! class_exists( 'WC_Software_License_Client' ) ) :

	/**
	 * Class responsible for a single product.
	 *
	 * @version 1.0.2
	 * @since   1.0.2
	 */
	//phpcs:ignore
	class WC_Software_License_Client {
		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		private static $instances = array();

		/**
		 * Version - current plugin version
		 *
		 * @var string $version
		 * @version 1.0.2
		 * @since   1.0.0
		 */
		public $version;

		/**
		 * License URL - The base URL for your woocommerce install
		 *
		 * @var string $license_server_url
		 * @version 1.0.2
		 * @since 1.0.0
		 */
		public $license_server_url;

		/**
		 * Slug - the plugin slug to check for updates with the server
		 *
		 * @var string $slug
		 * @version 1.0.2
		 * @since   1.0.0
		 */
		public $slug;

		/**
		 * Plugin text domain
		 *
		 * @var string $text_domain
		 * @version 1.0.2
		 * @since 1.0.0
		 */
		public $text_domain;

		/**
		 * Path to the plugin file or directory, relative to the plugins directory
		 *
		 * @var string $base_file
		 * @version 1.0.2
		 * @since   1.0.0
		 */
		public $base_file;

		/**
		 * Path to the plugin file or directory, relative to the plugins directory
		 *
		 * @var string $name
		 * @version 1.0.2
		 * @since   1.0.0
		 */
		public $name;

		/**
		 * Update interval - what period in hours to check for updates defaults to 12;
		 *
		 * @var string $update_interval
		 * @version 1.0.2
		 * @since   1.0.0
		 */
		public $update_interval;

		/**
		 * Option name - wp option name for license and update information stored as $slug_wc_software_license.
		 *
		 * @var string $option_name
		 * @version 1.0.2
		 * @since 1.0.0
		 */
		public $option_name;

		/**
		 * The license server host.
		 *
		 * @var string $version
		 * @version 1.0.2
		 * @since   1.0.2
		 */
		private $license_server_host;

		/**
		 * The plugin license key.
		 *
		 * @var string $version
		 * @version 1.0.2
		 * @since   1.0.2
		 */
		private $license_key;

		/**
		 * The domain the plugin is running on.
		 *
		 * @var string $version
		 * @version 1.0.2
		 * @since   1.0.2
		 */
		private $domain;

		/**
		 * The plugin license key.
		 *
		 * @var string $version
		 * @version 1.0.2
		 * @since   1.0.0
		 * @access private
		 */
		private $admin_notice;

		/**
		 * The current environment on which the client is install.
		 *
		 * @var     string
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		private $environment;

		/**
		 * Holds instance of WC_Software_License_Client_Manager class
		 *
		 * @var     WC_Software_License_Client_Manager
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public $client_manager;

		/**
		 * Whether to show the builtin settings page
		 *
		 * @var     bool
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public $show_settings_page;

		/**
		 * Don't allow cloning
		 *
		 * @since 1.0.0
		 */
		private function __clone() {}

		/**
		 * Don't allow unserializing instances of this class
		 *
		 * @since 1.0.0
		 */
		private function __wakeup() {}

		/**
		 * Return an instance of this class.
		 *
		 * @since   1.0.0
		 * @version 1.0.2
		 * @param   string  $license_server_url - The base url to your woocommerce shop
		 * @param   string  $base_file - path to the plugin file or directory, relative to the plugins directory
		 * @param   string  $sofware_type - the type of software this is. plugin|theme, default: plugin
		 * @param   integer $args - array of additional arguments to override default ones
		 * @return  object A single instance of this class.
		 */
		public static function get_instance( $license_server_url, $base_file, $software_type = 'plugin', $args = array() ) {

			$args = wp_parse_args( $args, wp_parse_args( self::get_default_args(), self::get_file_information( $base_file, $software_type ) ) );

			$text_domain = $args['text_domain'];
			if ( ! array_key_exists( $text_domain, self::$instances ) ) {
				self::$instances[ $text_domain ] = new self( $license_server_url, $base_file, $software_type, $args );
			}

			return self::$instances;

		} // get_instance()

		/**
		 * Initialize the class actions
		 *
		 * @since   1.0.0
		 * @version 1.0.2
		 * @param   string  $license_server_url - The base url to your woocommerce shop
		 * @param   string  $base_file - path to the plugin file or directory, relative to the plugins directory
		 * @param   string  $sofware_type - the type of software this is. plugin|theme, default: plugin
		 * @param   integer $args - array of additional arguments to override default ones
		 */
		private function __construct( $license_server_url, $base_file, $software_type, $args ) {

			if ( empty( $args ) ) {
				$args = $this->get_file_information( $base_file, $software_type );
			}
			extract( $args );

			$this->base_file          = $base_file;
			$this->name               = empty( $name ) ? $title : $name;
			$this->license_server_url = trailingslashit( $license_server_url );
			$this->version            = $version;
			$this->text_domain        = $text_domain;
			$this->show_settings_page = $show_settings_page;

			if ( 'plugin' === $software_type ) {
				$this->plugin_file = plugin_basename( $base_file );
				$this->slug        = empty( $slug ) ? basename( $this->plugin_file, '.php' ) : $slug;
			} else {
				$this->theme_file = $base_file;
				$this->slug       = empty( $slug ) ? basename( $this->theme_file, '.css' ) : $slug;
			}

			$this->update_interval = $update_interval;
			$this->debug           = defined( 'WP_DEBUG' ) && WP_DEBUG ? true : $debug;

			$this->option_name         = $this->slug . '_license_manager';
			$this->domain              = trailingslashit( str_ireplace( array( 'http://', 'https://' ), '', home_url() ) );
			$this->license_details     = get_option( $this->option_name );
			$this->software_type       = $software_type;
			$this->environment         = $environment;
			$this->license_manager_url = esc_url( admin_url( 'options-general.php?page=slswc_license_manager&tab=licenses' ) );

			$this->client_manager = WC_Software_License_Client_Manager::get_instance( $this->license_server_url, $this->slug, $this->text_domain );

			// Get the license server host
			$this->license_server_host = @parse_url( $this->license_server_url, PHP_URL_HOST );

			// Don't run the license activation code if running on local host.
			$whitelist = apply_filters( 'wcv_localhost_whitelist', array( '127.0.0.1', '::1' ) );

			if ( isset( $_SERVER['SERVER_ADDR'] ) && in_array( $_SERVER['SERVER_ADDR'], $whitelist ) && ! $debug ) {

				add_action( 'admin_notices', array( $this, 'license_localhost' ) );

			} else {

				// Initilize wp-admin interfaces.
				add_action( 'admin_init', array( $this, 'add_product' ) );
				add_action( 'admin_init', array( $this, 'check_install' ) );
				add_action( 'admin_menu', array( $this, 'add_license_menu' ) );
				add_action( 'admin_init', array( $this, 'add_license_settings' ) );

				// Internal methods.
				add_filter( 'http_request_host_is_external', array( $this, 'fix_update_host' ), 10, 2 );

				// Extra Plugin & Theme Header.
				add_filter( 'extra_plugin_headers', array( $this, 'extra_headers' ) );
				add_filter( 'extra_theme_headers', array( $this, 'extra_headers' ) );

				// Validate license on save.
				add_action( 'slswc_save_license_' . $this->slug, array( $this, 'validate_license' ) );

				// Only allow updates if they have a valid license key need.
				if ( 'active' === $this->license_details['license_status'] ) {
					if ( 'plugin' === $this->software_type ) {

						add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_check' ) );
						add_filter( 'plugins_api', array( $this, 'add_plugin_info' ), 10, 3 );
						add_filter( 'plugin_row_meta', array( $this, 'check_for_update_link' ), 10, 2 );
					} else {
						add_action( 'pre_set_site_transient_update_themes', array( $this, 'theme_update_check' ), 21, 1 );
						add_filter( 'themes_api', array( $this, 'add_theme_info' ), 10, 3 );
					}

					add_action( 'admin_init', array( $this, 'process_manual_update_check' ) );
					add_action( 'all_admin_notices', array( $this, 'output_manual_update_check_result' ) );
				}
			}
		} // __construct()

		/**
		 * Get the default args
		 *
		 * @return  array $args
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public static function get_default_args() {
			return array(
				'update_interval'    => 12,
				'debug'              => false,
				'environment'        => 'live',
				'show_settings_page' => false,
			);
		}

		/**
		 * Check the installation and configure any defaults that are required
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @todo move this to a plugin activation hook
		 */
		public function check_install() {

			// Set defaults
			if ( empty( $this->license_details ) ) {
				$default_license_options = array(
					'license_status'     => 'inactive',
					'license_key'        => '',
					'license_expires'    => '',
					'deactivate_license' => '',
					'current_version'    => $this->version,
					'environment'        => $this->environment,
				);

				update_option( $this->option_name, $default_license_options );
			}

			if ( '' === $this->license_details || 'inactive' === $this->license_details['license_status'] || 'deactivated' === $this->license_details['license_status'] ) {
				add_action( 'admin_notices', array( $this, 'license_inactive' ) );
			}

			if ( 'expired' == $this->license_details['license_status'] && 'active' === $this->license_details['license_status'] ) {
				add_action( 'admin_notices', array( $this, 'license_inactive' ) );
			}

		} // check_install()


		/**
		 * Display a license inactive notice
		 */
		public function license_inactive() {

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			echo '<div class="error notice is-dismissible"><p>' .
			sprintf( __( 'The %1$s license key has not been activated, so you will be unable to get automatic updates or support! %2$sClick here%3$s to activate your support and updates license key.', $this->text_domain ), $this->name, '<a href="' . $this->license_manager_url . '">', '</a>' ) .
			'</p></div>';

		} // license_inactive()

		/**
		 * Display the localhost detection notice
		 */
		public function license_localhost() {

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			echo '<div class="error notice is-dismissible"><p>' . sprintf( __( '%s has detected you are running on your localhost. The license activation system has been disabled. ', $text_domain ), $this->name ) . '</p></div>';

		} // license_localhost()

		/**
		 * Check for updates with the license server
		 *
		 * @since 1.0.0
		 * @param object transient object from the update api
		 * @return object transient object possibly modified
		 */
		public function update_check( $transient ) {

			if ( empty( $transient->checked ) ) {
				return $transient;
			}

			$server_response = $this->server_request( 'check_update' );

			if ( $this->check_license( $server_response ) ) {

				if ( isset( $server_response ) && is_object( $server_response->software_details ) ) {

					$plugin_update_info = $server_response->software_details;

					if ( isset( $plugin_update_info->new_version ) ) {
						if ( version_compare( $plugin_update_info->new_version, $this->version, '>' ) ) {
							// Required to cast as array due to how object is returned from api
							$plugin_update_info->sections              = (array) $plugin_update_info->sections;
							$transient->response[ $this->plugin_file ] = $plugin_update_info;
						}
					}
				}
			}

			return $transient;

		} // update_check()

		/**
		 * Check if there are updates for themes
		 *
		 * @param   mixed $transient
		 * @return  void
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function theme_update_check( $transient ) {

			if ( empty( $transient->checked ) ) {
				return $transient;
			}

			$server_response = $this->server_request( 'check_update' );

			if ( $this->check_license( $server_response ) ) {

				if ( isset( $server_response ) && is_object( $server_response->software_details ) ) {

					$theme_update_info = $server_response->software_details;

					if ( isset( $theme_update_info->new_version ) ) {
						if ( version_compare( $theme_update_info->new_version, $this->version, '>' ) ) {
							// Required to cast as array due to how object is returned from api.
							$theme_update_info->sections = (array) $theme_update_info->sections;

							// Theme name.
							$transient->response[ $this->theme_file ] = $theme_update_info;
						}
					}
				}
			} else {
				error_log( 'License not verified for theme: ' . print_r( $server_response, true ) );
			}

			return $transient;
		}

		/**
		 * Add the plugin information to the WordPress Update API
		 *
		 * @since 1.0.0
		 * @param bool|object The result object. Default false.
		 * @param string The type of information being requested from the Plugin Install API.
		 * @param object Plugin API arguments.
		 * @return object
		 */
		public function add_plugin_info( $result, $action = null, $args = null ) {

			// Is this about our plugin?
			if ( isset( $args->slug ) ) {

				if ( $args->slug != $this->slug ) {
					return $result;
				}
			} else {
				return $result;
			}

			$server_response    = $this->server_request();
			$plugin_update_info = $server_response->software_details;
			// Required to cast as array due to how object is returned from api
			$plugin_update_info->sections = (array) $plugin_update_info->sections;

			if ( isset( $plugin_update_info ) && is_object( $plugin_update_info ) && $plugin_update_info !== false ) {
				return $plugin_update_info;
			}

			return $result;

		} // add_plugin_info()

		/**
		 * Add the theme information to the theme update API.
		 *
		 * @param   bool   $override
		 * @param   string $action
		 * @param   array  $args
		 * @return  void
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function add_theme_info( $override, $action, $args ) {
			global $theme_base, $api_url, $theme_version, $api_url;

			if ( $args->slug != $theme_base ) {
				return false;
			}

			// Get the current version.
			$args->version  = $this->version;
			$request_string = prepare_request( $action, $args );
			$request        = $this->server_request( $action, $args );
			error_log( 'Theme info check:: ' . print_r( $request, true ) );
			if ( is_wp_error( $request ) ) {
				$result = new WP_Error(
					'themes_api_failed',
					__( 'An unexpected HTTP error occurred. Please retry.', $this->text_domain ),
					$request->get_error_message()
				);
			} else {
				$result = unserialize( $request['body'] );

				if ( $result === false ) {
					$result = new WP_Error(
						'themes_api_failed',
						__( 'An unknown error occurred.', $this->text_domain ),
						$request['body']
					);
				}
			}

			error_log( 'Theme update result:: ' . print_r( $result, true ) );

			return $result;
		}

		/**
		 * Send a request to the server
		 *
		 * @param $action string activate|deactivate|check_update
		 */
		public function server_request( $action = 'check_update', $request_info = array() ) {

			if ( empty( $request_info ) && ! $this->client_manager->is_connected() ) {
				$request_info['slug']        = $this->slug;
				$request_info['license_key'] = $this->license_details['license_key'];
				$request_info['domain']      = $this->domain;
				$request_info['version']     = $this->version;
				$request_info['environment'] = $this->environment;
			} elseif ( $this->client_manager->is_connected() ) {
				$request_info['slug']        = $this->slug;
				$request_info['domain']      = $this->domain;
				$request_info['version']     = $this->version;
				$request_info['environment'] = $this->environment;

				$request_info = array_merge( $request_info, $this->client_manager->get_api_keys() );
			}

			return $this->client_manager->server_request( $action, $request_info );

		} // server_request()


		/**
		 * Validate the license is active and if not, set the status and return false
		 *
		 * @since 1.0.0
		 * @access public
		 * @param object $response_body
		 */
		public function check_license( $response_body ) {

			$status = $response_body->status;

			if ( 'active' === $status || 'expiring' === $status ) {
				return true;
			}

			$this->set_license_status( $status );
			$this->set_license_expires( $response_body->expires );
			$this->save();

			return false;

		} // check_license()


		/**
		 * Add a check for update link on the plugins page. You can change the link with the supplied filter.
		 * returning an empty string will disable this link
		 *
		 * @since 1.0.0
		 * @access public
		 * @param array  $links The array having default links for the plugin.
		 * @param string $file The name of the plugin file.
		 */
		public function check_for_update_link( $links, $file ) {

			// Only modify the plugin meta for our plugin
			if ( $file == $this->plugin_file && current_user_can( 'update_plugins' ) ) {

				$update_link_url = wp_nonce_url(
					add_query_arg(
						array(
							'wcsl_check_for_update' => 1,
							'wcsl_slug'             => $this->slug,
						),
						self_admin_url( 'plugins.php' )
					),
					'wcsl_check_for_update'
				);

				$update_link_text = apply_filters( 'wcsl_update_link_text_' . $this->slug, __( 'Check for updates', $this->text_domain ) );

				if ( ! empty( $update_link_text ) ) {
					$links[] = sprintf( '<a href="%s">%s</a>', esc_attr( $update_link_url ), $update_link_text );
				}
			}

			return $links;

		} // check_for_update_link()

		/**
		 * Process the manual check for update if check for update is clicked on the plugins page.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function process_manual_update_check() {

			if ( isset( $_GET['wcsl_check_for_update'], $_GET['wcsl_slug'] ) && $_GET['wcsl_slug'] == $this->slug && current_user_can( 'update_plugins' ) && check_admin_referer( 'wcsl_check_for_update' ) ) {

				// Check for updates
				$server_response = $this->server_request();

				if ( $this->check_license( $server_response ) ) {

					$plugin_update_info = $server_response->software_details;

					if ( isset( $plugin_update_info ) && is_object( $plugin_update_info ) ) {

						if ( version_compare( (string) $plugin_update_info->new_version, (string) $this->version, '>' ) ) {

							$update_available = true;

						} else {

							$update_available = false;
						}
					} else {

						$update_available = false;
					}

					$status = ( $update_available == null ) ? 'no' : 'yes';

					wp_redirect(
						add_query_arg(
							array(
								'wcsl_update_check_result' => $status,
								'wcsl_slug'                => $this->slug,
							),
							self_admin_url( 'plugins.php' )
						)
					);
				}
			}

		} // process_manual_update_check()


		/**
		 * Out the results of the manual check
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function output_manual_update_check_result() {

			if ( isset( $_GET['wcsl_update_check_result'], $_GET['wcsl_slug'] ) && ( $_GET['wcsl_slug'] == $this->slug ) ) {

				$check_result = $_GET['wcsl_update_check_result'];

				switch ( $check_result ) {
					case 'no':
						$admin_notice = __( 'This plugin is up to date. ', $this->text_domain );
						break;
					case 'yes':
						$admin_notice = sprintf( __( 'An update is available for %s.', $this->text_domain ), $this->name );
						break;
					default:
						$admin_notice = __( 'Unknown update status.', $this->text_domain );
						break;
				}

				printf( '<div class="updated notice is-dismissible"><p>%s</p></div>', apply_filters( 'wcsl_manual_check_message_result_' . $this->slug, $admin_notice, $check_result ) );
			}

		} // output_manual_update_check_result()


		/**
		 * This is for internal purposes to ensure that during development the HTTP requests go through
		 * due to security features in the WordPress HTTP API.
		 *
		 * Source for this solution: Plugin Update Checker Library 3387.1 by Janis Elsts
		 *
		 * @since 1.0.0
		 * @access public
		 * @param bool   $allow
		 * @param string $host
		 * @return bool
		 */
		private function fix_update_host( $allow, $host ) {

			if ( strtolower( $host ) === strtolower( $this->license_server_url ) ) {
				return true;
			}
			return $allow;

		} //fix_update_host()


		/**
		 * Class logger so that we can keep our debug and logging information cleaner
		 *
		 * @since 1.0.0
		 * @access public
		 * @param mixed - the data to go to the error log
		 */
		private function log( $data ) {

			if ( is_array( $data ) || is_object( $data ) ) {
				error_log( __CLASS__ . ' : ' . print_r( $data, true ) );
			} else {
				error_log( __CLASS__ . ' : ' . $data );
			}

		} // log()

		/**
		 * Add the admin menu to the dashboard
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function add_license_menu() {
			if ( $this->show_settings_page ) {
				$page = add_options_page(
					sprintf( __( '%s License', $this->text_domain ), $this->name ),
					sprintf( __( '%s License', $this->text_domain ), $this->name ),
					'manage_options',
					$this->slug . '_license_manager',
					array( $this, 'load_license_page' )
				);
			}
		} // add_license_menu()

		/**
		 * Load settings for the admin screens so users can input their license key
		 *
		 * Utilizes the WordPress Settings API to implment this
		 *
		 * @since 1.0.0
		 * @access public
		 * TODO: Remove settings functions related to old settings page
		 */
		public function add_license_settings() {

			register_setting( $this->option_name, $this->option_name, array( $this, 'validate_license' ) );

			// License key section
			add_settings_section(
				$this->slug . '_license_activation',
				__( 'License Activation', $this->text_domain ),
				array( $this, 'license_activation_section_callback' ),
				$this->option_name
			);

			// License key
			add_settings_field(
				'license_key',
				__( 'License key', $this->text_domain ),
				array( $this, 'license_key_field' ),
				$this->option_name,
				$this->slug . '_license_activation'
			);

			// License status
			add_settings_field(
				'license_status',
				__( 'License Status', $this->text_domain ),
				array( $this, 'license_status_field' ),
				$this->option_name,
				$this->slug . '_license_activation'
			);

			// License expires
			add_settings_field(
				'license_expires',
				__( 'License Expires', $this->text_domain ),
				array( $this, 'license_expires_field' ),
				$this->option_name,
				$this->slug . '_license_activation'
			);

			// License environment
			add_settings_field(
				'license_environment',
				__( 'This is a Staging Site', $this->text_domain ),
				array( $this, 'licence_environment_field' ),
				$this->option_name,
				$this->slug . '_environment'
			);

			// Deactivate license checkbox
			add_settings_field(
				'deactivate_license',
				__( 'Deactivate license', $this->text_domain ),
				array( $this, 'license_deactivate_field' ),
				$this->option_name,
				$this->slug . '_license_activation'
			);

		} // add_license_page()

		/**
		 * License page output call back function
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function load_license_page() {
		?>
		<div class='wrap'>
		<h2><?php printf( __( '%s License Manager', $this->text_domain ), $this->name ); ?></h2>
		<form action='options.php' method='post'>
			<div class="main">
				<div class="notice update">
				<?php printf( __( 'Please Note: If your license is active on another website you will need to deactivate this in your wcvendors.com my downloads page before being able to activate it on this site.  IMPORTANT:  If this is a development or a staging site dont activate your license.  Your license should ONLY be activated on the LIVE WEBSITE you use Pro on.', $this->text_domain ), $this->name ); ?>
				</div>

				<?php // settings_errors(); ?>

				<?php
					settings_fields( $this->option_name );
					do_settings_sections( $this->option_name );
					submit_button( __( 'Save Changes', $this->text_domain ) );
				?>
				</div>
			</form>
		</div>

		<?php
		} // license_page()

		/**
		 * Add the product to the list
		 *
		 * @return  void
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public function add_product() {

			$product = array(
				'text_domain' => $this->text_domain,
				'plugin_file' => $this->base_file,
				'name'        => $this->name,
				'slug'        => $this->slug,
				'type'        => $this->software_type,
			);

			$product = wp_parse_args( $product, $this->get_file_information( $product['plugin_file'] ), $product['type'] );

			$this->client_manager->add_product( $product );
		}

		/**
		 * License activation settings section callback
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function license_activation_section_callback() {

			echo '<p>' . __( 'Please enter your license key to activate automatic updates and verify your support.', $this->text_domain ) . '</p>';

		} // license_activation_section_callback ()

		/**
		 * License key field callback
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function license_key_field() {
			$value = ( isset( $this->license_details['license_key'] ) ) ? $this->license_details['license_key'] : '';
			echo '<input type="text" id="license_key" name="' . $this->option_name . '[license_key]" value="' . $value . '" />';

		} // license_key_field()

		/**
		 * License acivated field
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function license_status_field() {

			$license_labels = $this->license_status_types();

			_e( $license_labels[ $this->license_details['license_status'] ] );

		} // license_status_field()

		/**
		 * License acivated field
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function license_expires_field() {
			echo $this->license_details['license_expires'];
		}

		/**
		 * License deactivate checkbox
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function license_deactivate_field() {

			echo '<input type="checkbox" id="deactivate_license" name="' . $this->option_name . '[deactivate_license]" />';

		} // license_deactivate_field()

		/**
		 * The current server environment
		 *
		 * @return  void
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function licence_environment_field() {
			echo '<input type="checkbox" id="environment" name="' . $this->option_name . '[environment]" />';
		}

		/**
		 * Validate the license key information sent from the form.
		 *
		 * @since 1.0.0
		 * @access public
		 * @param array $input the input passed from the request
		 */
		public function validate_license( $input ) {

			$options = $this->license_details;
			$type    = null;
			$message = null;
			$expires = '';

			foreach ( $options as $key => $value ) {

				if ( 'license_key' === $key ) {

					if ( 'active' === $this->get_license_status() ) {
						continue;
					}

					if ( ! array_key_exists( 'deactivate_license', $input ) || 'deactivated' !== $this->get_license_status() ) {

						$this->license_details['license_key'] = $input[ $key ];
						$response                             = $this->server_request( 'activate' );

						if ( $response !== null ) {

							error_log("Response:: " . print_r( $response, true ) );

							if ( $this->client_manager->check_response_status( $response ) ) {

								$options[ $key ]            = $input[ $key ];
								$options['license_status']  = $response->status;
								$options['license_expires'] = $response->expires;

								if ( $response->status === 'valid' || $response->status === 'active' ) {
									$type    = 'updated';
									$message = __( 'License activated.', $this->text_domain );
								} else {
									$type    = 'error';
									$message = $response->message;
								}
							} else {

								$type    = 'error';
								$message = __( 'Invalid License', $this->text_domain );
							}

							add_settings_error(
								$this->option_name,
								esc_attr( 'settings_updated' ),
								$message,
								$type
							);

							$options[ $key ] = $input[ $key ];
						}
					}

					$options[ $key ] = $input[ $key ];

				} elseif ( array_key_exists( $key, $input ) && 'deactivate_license' === $key ) {

					$response = $this->server_request( 'deactivate' );

					if ( $response !== null ) {

						if ( $this->client_manager->check_response_status( $response ) ) {
							$options[ $key ]            = $input[ $key ];
							$options['license_status']  = $response->status;
							$options['license_expires'] = $response->expires;
							$type                       = 'updated';
							$message                    = __( 'License Deactivated', $this->text_domain );

						} else {

							$type    = 'updated';
							$message = __( 'Unable to deactivate license. Please deactivate on the store.', $this->text_domain );

						}

						add_settings_error(
							$this->option_name,
							esc_attr( 'settings_updated' ),
							$message,
							$type
						);
					}
				} elseif ( 'license_status' === $key ) {

					if ( empty( $options['license_status'] ) ) {
						$options['license_status'] = 'inactive';
					} else {
						$options['license_status'] = $options['license_status'];
					}
				} elseif ( 'license_expires' === $key ) {

					if ( empty( $options['license_expires'] ) ) {
						$options['license_expires'] = '';
					} else {
						$options['license_expires'] = date( 'Y-m-d', strtotime( $options['license_expires'] ) );
					}
				}
			}

			return $options;

		} // validate_license()

		/**
		 * The available license status types
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function license_status_types() {

			return apply_filters(
				'wcsl_license_status_types',
				array(
					'valid'           => __( 'Valid', $this->text_domain ),
					'deactivated'     => __( 'Deactivated', $this->text_domain ),
					'max_activations' => __( 'Max Activations reached', $this->text_domain ),
					'invalid'         => __( 'Invalid', $this->text_domain ),
					'inactive'        => __( 'Inactive', $this->text_domain ),
					'active'          => __( 'Active', $this->text_domain ),
					'expiring'        => __( 'Expiring', $this->text_domain ),
					'expired'         => __( 'Expired', $this->text_domain ),
				)
			);

		} // software_types()


		/**
		 * --------------------------------------------------------------------------
		 * Getters
		 * --------------------------------------------------------------------------
		 *
		 * Methods for getting object properties
		 */

		/**
		 * Get the license setatus
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function get_license_status() {

			return $this->license_details['license_status'];

		} // get_license_status()

		/**
		 * Get the license key
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function get_license_key() {

			return $this->license_details['license_key('];

		} // get_license_key()


		/**
		 * Get the license expiry
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function get_license_expires() {

			return $this->license_details['license_expires'];

		} // get_license_expires()

		/**
		 * Get theme or plugin information from file
		 *
		 * @param   string $base_file - Plugin file or theme slug
		 * @param   string $type - Product type. plugin|theme
		 * @return  array
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public static function get_file_information( $base_file, $type = 'plugin' ) {
			$data = array();
			if ( 'plugin' === $type ) {
				if ( ! function_exists( 'get_plugin_data' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}
				$plugin = get_plugin_data( $base_file, false );

				$data = array(
					'name'              => $plugin['Name'],
					'title'             => $plugin['Title'],
					'description'       => $plugin['Description'],
					'author'            => $plugin['Author'],
					'author_uri'        => $plugin['AuthorURI'],
					'version'           => $plugin['Version'],
					'plugin_url'        => $plugin['PluginURI'],
					'text_domain'       => $plugin['TextDomain'],
					'domain_path'       => $plugin['DomainPath'],
					'network'           => $plugin['Network'],

					// SLS WC Headers
					'slswc'             => ! empty( $plugin['SLSWC'] ) ? $plugin['SLSWC'] : '',
					'slug'              => ! empty( $plugin['Slug'] ) ? $plugin['Slug'] : $plugin['TextDomain'],
					'required_wp'       => ! empty( $plugin['RequiredWP'] ) ? $plugin['RequiredWP'] : '',
					'compatible_to'     => ! empty( $plugin['CompatibleTo'] ) ? $plugin['CompatibleTo'] : '',
					'documentation_url' => ! empty( $plugin['DocumentationURL'] ) ? $plugin['DocumentationURL'] : '',
				);
			} elseif ( 'theme' === $type ) {
				if ( ! function_exists( 'wp_get_theme' ) ) {
					require_once ABSPATH . 'wp-includes/theme.php';
				}
				$theme = wp_get_theme( basename( $base_file ) );

				$data = array(
					'name'              => $theme->get( 'Name' ),
					'theme_url'         => $theme->get( 'ThemeURI' ),
					'description'       => $theme->get( 'Description' ),
					'author'            => $theme->get( 'Author' ),
					'author_url'        => $theme->get( 'AuthorURI' ),
					'version'           => $theme->get( 'Version' ),
					'template'          => $theme->get( 'Template' ),
					'status'            => $theme->get( 'Status' ),
					'tags'              => $theme->get( 'Tags' ),
					'text_domain'       => $theme->get( 'TextDomain' ),
					'domain_path'       => $theme->get( 'DomainPath' ),
					// SLS WC Headers
					'slswc'             => ! empty( $theme->get( 'SLSWC' ) ) ? $theme->get( 'SLSWC' ) : '',
					'slug'              => ! empty( $theme->get( 'Slug' ) ) ? $theme->get( 'Slug' ) : $theme->get( 'TextDomain' ),
					'required_wp'       => ! empty( $theme->get( 'RequiredWP' ) ) ? $theme->get( 'RequiredWP' ) : '',
					'compatible_to'     => ! empty( $theme->get( 'CompatibleTo' ) ) ? $theme->get( 'CompatibleTo' ) : '',
					'documentation_url' => ! empty( $theme->get( 'DocumentationURL' ) ) ? $theme->get( 'DocumentationURL' ) : '',
				);
			}

			return $data;

		}

		/**
		 * Add extra plugin/theme headers
		 *
		 * @param   array $headers
		 * @return  void
		 * @since   1.0.2
		 * @version 1.0.2
		 */
		public static function extra_headers( $current_extra_headers ) {

			$new_extra_headers = array(
				'SLSWC'             => __( 'SLSWC', $this->text_domain ),
				'Slug'              => __( 'Slug', $this->text_domain ),
				'RequiredWP'        => __( 'Required WP', $this->text_domain ),
				'CompatibleTo'      => __( 'Compatible To', $this->text_domain ),
				'Documentation URL' => __( 'Documentation URL', $this->text_domain ),
			);

			$extra_headers = empty( $current_extra_headers ) ? $new_extra_headers : array_merge( $current_extra_headers, $new_extra_headers );
			return $extra_headers;
		}


		/**
		 * --------------------------------------------------------------------------
		 * Setters
		 * --------------------------------------------------------------------------
		 *
		 * Methods to set the object properties for this instance. This does not
		 * interact with the database.
		 */

		/**
		 * Set the license status
		 *
		 * @since 1.0.0
		 * @access public
		 * @param string $license_status
		 */
		public function set_license_status( $license_status ) {

			$this->license_details['license_status'] = $license_status;

		} // set_license_status()

		/**
		 * Set the license key
		 *
		 * @since 1.0.0
		 * @access public
		 * @param string $license_key
		 */
		public function set_license_key( $license_key ) {

			$this->license_details['license_key'] = $license_key;

		} // set_license_key()

		/**
		 * Set the license expires
		 *
		 * @since 1.0.0
		 * @access public
		 * @param string $license_expires
		 */
		public function set_license_expires( $license_expires ) {

			$this->license_details['license_expires'] = $license_expires;

		} // set_license_expires()

		public function save() {

			update_option( $this->option_name, $this->license_details );

		} // save()


	} // WC_Software_License_Client

endif;
