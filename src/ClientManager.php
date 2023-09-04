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
 * @since   1.1.0 - Refactored into classes and converted into a composer package.
 * @version 1.1.0
 */
//phpcs:ignore
class ClientManager {
    /**
     * Instance of this class.
     *
     * @since 1.1.0
     * @var object
     */
    private static $instance = null;

    /**
     * Version - current plugin version.
     *
     * @since 1.1.0
     * @var string
     */
    public $version;

    /**
     * Plugin text domain.
     *
     * @since 1.1.0
     *
     * @var string
     */
    public $text_domain;

    /**
     * Holds a list of plugins
     *
     * @var array
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     */
    public $plugins = [];

    /**
     * Holds a list of themes
     *
     * @var array
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     */
    public $themes = [];

    /**
     * List of products
     *
     * @var     array
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     * @version 1.1.0
     */
    public $products;

    /**
     * Status update messages
     *
     * @var array
     * @version 1.0.1
     * @since   1.0.1
     */
    public $messages = [];

    /**
     * The localization strings
     *
     * @var array
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     */
    public $localization = [];

    /**
     * The url of the license server
     *
     * @var string
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     */
    public $server_url;

    /**
     * The ApiClient instance
     *
     * @var ApiClient
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     */
    public $client;

    /**
     * The updaters for each plugin.
     *
     * @var array
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     */
    public $updaters = [];


    /**
     * Return instance of this class
     *
     * @param   string $server_url The url to the license server.
     * @return  object
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     * @version 1.1.0
     */
    public static function get_instance( $server_url ) {
        if ( null === self::$instance ) {
            self::$instance = new self( $server_url );
        }

        return self::$instance;
    } // get_instance

    /**
     * Initialize the class actions
     *
     * @param string $server_url The url to the license server.
     *
     * @since 1.1.0
     * @version 1.1.0
     */
    private function __construct( $server_url ) {
        $this->server_url = $server_url;

        $this->client = new ApiClient( $this->server_url, 'slswc-client' );

        $this->plugins = $this->get_local_plugins();
        $this->themes  = $this->get_local_themes();

        $this->products = [
            'plugins' => $this->get_plugins(),
            'themes'  => $this->get_themes(),
        ];

        foreach ( $this->get_plugins() as $plugin ) {
            $this->updaters[ $plugin['slug'] ] = new Plugin( $this->server_url, $plugin['slug'] );
        }

        foreach ( $this->get_themes() as $theme ) {
            $this->updaters[ $theme['slug'] ] = new Theme( $this->server_url, $theme['slug'] );
        }

        $this->localization = [
            'ajax_url'        => esc_url( admin_url( 'admin-ajax.php' ) ),
            'loader_url'      => esc_url( admin_url( 'images/loading.gif' ) ),
            'text_activate'   => esc_attr( __( 'Activate', 'slswcclient' ) ),
            'text_deactivate' => esc_attr( __( 'Deactivate', 'slswcclient' ) ),
            'text_done'       => esc_attr( __( 'Done', 'slswcclient' ) ),
            'text_processing' => esc_attr( __( 'Processing', 'slswcclient' ) ),
        ];
    }

    /**
     * Initialize hooks
     *
     * @return void
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     */
    public function init_hooks() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'wp_ajax_slswc_install_product', [ $this, 'product_background_installer' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
        add_action( 'init', [ $this, 'init_products' ], 1 );
    }

    /**
     * Initialize products
     *
     * @return void
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     */
    public function init_products() {
        $this->get_local_plugins();
        $this->get_local_themes();

        // $this->add_unlicensed_products_notices();
    }

    /**
     * Get local themes.
     *
     * Get locally installed themes that have SLSWC file headers.
     *
     * @return  array $installed_themes List of plugins.
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     */
    public function get_local_themes() {
        if ( ! function_exists( 'wp_get_themes' ) ) {
            return [];
        }

        $themes = wp_cache_get( 'slswc_themes', 'slswc' );

        if ( $themes == false ) {
            $wp_themes = wp_get_themes();
            $themes    = [];

            foreach ( $wp_themes as $theme_file => $theme_details ) {
                if ( ! $theme_details->get( 'SLSWC' ) || 'theme' !== $theme_details->get( 'SLSWC' ) ) {
                    continue;
                }
                $theme_data                              = Helper::format_theme_data( $theme_details, $theme_file );
                $themes[ $theme_details->get( 'Slug' ) ] = wp_parse_args( $theme_data, $this->default_remote_product( 'theme' ) );
            }
        }

        $this->set_themes( $themes );

        wp_cache_add( 'slswc_themes', $themes, 'slswc', apply_filters( 'slswc_themes_cache_expiry', HOUR_IN_SECONDS * 2 ) );

        return $themes;
    }

    /**
     * Get local plugins.
     *
     * Get locally installed plugins that have SLSWC file headers.
     *
     * @return  array $installed_plugins List of plugins.
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     */
    public function get_local_plugins() {
        if ( ! function_exists( 'get_plugins' ) ) {
            return [];
        }

        $plugins = wp_cache_get( 'slswc_plugins', 'slswc' );

        if ( $plugins === false ) {
            $plugins    = [];
            $wp_plugins = get_plugins();

            foreach ( $wp_plugins as $plugin_file => $plugin_details ) {
                if ( ! isset( $plugin_details['SLSWC'] ) || 'plugin' !== $plugin_details['SLSWC'] ) {
                    continue;
                }

                $plugin_data                     = Helper::format_plugin_data( $plugin_details, $plugin_file, 'plugin' );
                $plugins[ $plugin_data['slug'] ] = wp_parse_args( $plugin_data, $this->default_remote_product( 'theme' ) );
            }

            wp_cache_add(
                'slswc_plugins',
                $plugins,
                'slswc',
                apply_filters( 'slswc_plugins_cache_expiry', HOUR_IN_SECONDS * 2 )
            );
        }

        $this->set_plugins( $plugins );

        $this->save_products( $plugins );

        return $plugins;
    }

    /**
     * Get default remote product data
     *
     * @param   string $type The software type. Expects plugin, theme or other. Default plugin.
     * @return  array $default_data The default product data.
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     */
    public function default_remote_product( $type = 'plugin' ) {
        $default_data = [
            'thumbnail'      => '',
            'updated'        => gmdate( 'Y-m-d' ),
            'reviews_count'  => 0,
            'average_rating' => 0,
            'activations'    => 0,
            'type'           => $type,
            'download_url'   => '',
        ];

        return $default_data;
    }

    /**
     * Activate the updater
     *
     * @return void
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     */
    public function activate() {
        update_option( 'slswc_update_client_version', $this->version );
    }

    /**
     * Enqueue scripts.
     *
     * @return  void
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     */
    public function admin_enqueue_scripts() {
        if ( $this->is_products_page() ) {
            wp_register_script(
                'slswc-client-products',
                plugins_url( 'assets/js/products.js', dirname( __FILE__ ) ),
                [ 'jquery', 'thickbox' ],
                $this->version,
                true
            );

            wp_enqueue_script( 'slswc-client-products' );
            wp_enqueue_script( 'thickbox' );
            wp_enqueue_style( 'thickbox' );
        }

        if ( $this->is_licenses_tab() ) {
            wp_register_script(
                'slswc-client-licenses',
                plugins_url( 'assets/js/licenses.js', dirname( __FILE__ ) ),
                [ 'jquery' ],
                $this->version,
                true
            );
            wp_enqueue_script( 'slswc-client-licenses' );
        }
    }

    /**
     * Check if the current page is license page.
     *
     * @return  boolean
     * @version 1.0.3
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     */
    public function is_licenses_page() {
        $page = 'slswc_license_manager';
		// phpcs:disable
		$is_page = isset( $_GET['page'] ) && $page === $_GET['page'] ? true : false;
		$is_tab  = isset( $_GET['tab'] ) && $_GET['tab'] === '' ? true : false;
		// phpcs:enable
        if ( is_admin() && $is_page && $is_tab ) {
            return true;
        }

        return false;
    }

    /**
     * Check if the current page is a product list page.
     *
     * @return  boolean
     * @version 1.0.3
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     */
    public function is_products_page() {
        $tabs = [ 'plugins', 'themes' ];
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
    public function is_licenses_tab() {
        $tab  = $this->get_tab();
        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

        return is_admin() && $tab == 'licenses' && $page == 'slswc_license_manager';
    }

    /**
     * ------------------------------------------------------------------
     * Output Functions
     * ------------------------------------------------------------------
     */

    /**
     * Add the admin menu to the dashboard
     *
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     * @version 1.1.0
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'License Manager', 'slswcclient' ),
            __( 'License Manager', 'slswcclient' ),
            'manage_options',
            'slswc_license_manager',
            [ $this, 'show_installed_products' ]
        );
    }

    /**
     * List all products installed on this server.
     *
     * @return  void
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     * @version 1.1.0
     */
    public function show_installed_products() {
		// phpcs:ignore
		$tab = $this->get_tab();
        $license_admin_url = admin_url( 'options-general.php?page=slswc_license_manager' );

        ?>
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
                $connected = $this->connect();
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

            if ( ! empty( $_POST['disconnect_nonce'] )
                && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['disconnect_nonce'] ) ), 'disconnect' )
            ) {
                update_option( 'slswc_api_connected', 'no' );
            }

            $_all_products = array_merge( $this->plugins, $this->themes );
            foreach ( $_all_products as $_product ) {
                $option_name = esc_attr( $_product['slug'] ) . '_license_manager';

                settings_errors( $option_name );
            }

            require_once SLSWC_CLIENT_PARTIALS_DIR . 'panels.php';
    }

    /**
     * Output licenses form
     *
     * @return  void
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     * @version 1.1.0
     */
    public function licenses_form() {
        if (
            ! empty( $_POST['licenses'] )
            && ! empty( $_POST['save_licenses_nonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['save_licenses_nonce'] ) ), 'save_licenses' )
        ) {
			// phpcs:ignore
			$post_licenses = isset( $_POST['licenses'] ) ? wp_unslash( $_POST['licenses'] ) : array();

            if ( ! empty( $post_licenses ) ) {
                foreach ( $post_licenses as $slug => $license_details ) {
                    $license_details = Helper::recursive_parse_args(
                        $license_details,
                        [
                            'license_status'  => 'inactive',
                            'license_key'     => '',
                            'license_expires' => '',
                            'current_version' => $this->version,
                            'environment'     => '',
                        ]
                    );

                    do_action( "slswc_save_license_{$slug}", $license_details );
                }
            }
        }

        $this->display_messages();
        ?>
        <h2 class="screen-reader-text"><?php echo esc_attr( __( 'Licenses', 'slswcclient' ) ); ?></h2>
        <div id="license-action-response-message"></div>
        <form name="licenses-form" action="" method="post">
            <?php wp_nonce_field( 'save_licenses', 'save_licenses_nonce' ); ?>
            <div id="the-list">
                <?php
                if ( ! empty( $this->plugins ) ):
                    $this->licenses_rows( $this->plugins );
                    do_action( 'slswc_after_plugins_licenses_rows' );
                endif;

                if ( ! empty( $this->themes ) ):
                    $this->licenses_rows( $this->themes );
                    do_action( 'slswc_after_themes_licenses_list' );
                    endif;
                ?>
                <?php do_action( 'slswc_after_products_licenses_list', $this->plugins, $this->themes ); ?>
            </div>
        </form>
        <?php
    }

    /**
     * Licenses rows output
     *
     * @param   array $products The list of software products.
     * @return  void
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     * @version 1.1.0
     */
    public function licenses_rows( $products ) {
        foreach ( $products as $product ):
            $slug         = esc_attr( $product['slug'] );
            $option_name  = $slug . '_license_manager';
            $license_info = get_option( $option_name, [] );
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

            require_once SLSWC_CLIENT_PARTIALS_DIR . 'licenses-row.php';
        endforeach;
    }

    /**
     * Output a list of products.
     *
     * @param   array $products The list of products.
     * @return  void
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     * @version 1.1.0
     */
    public function list_products( $products ) {
        $products = is_array( $products ) ? $products : (array) $products;

        $type = $this->get_tab();

        if ( in_array( $type, [ 'plugins', 'themes' ], true ) ) {
            $slugs    = [];
            $licenses = [];
            foreach ( $products as $slug => $details ) {
                $slugs[]           = $slug;
                $licenses[ $slug ] = $details;
            }
            $args            = [ 'post_name__in' => $slugs ];
            $remote_products = (array) $this->get_remote_products( $type, $args );
        } else {
            $remote_products = [];
        }

        Helper::log( 'Local products.' );
        Helper::log( $products );
        Helper::log( 'Remote Products' );
        Helper::log( $remote_products );

        if ( ! empty( $products ) && count( $products ) > 0 ):
            require_once SLSWC_CLIENT_PARTIALS_DIR . 'products-list.php';
        else:
            require_once SLSWC_CLIENT_PARTIALS_DIR . 'no-products.php';
        endif;
    }

    /**
     * Output API Settings form
     *
     * @return void
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     * @version 1.1.0
     */
    public function api_form() {
        $keys = Helper::get_api_keys();
        require_once SLSWC_CLIENT_PARTIALS_DIR . 'api-keys.php';
    }

    /**
     * Output the product ratings
     *
     * @return void
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     * @version 1.1.0
     *
     * @param array $args The options for the rating.
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
     * @param   string $version - The version to compare with installed WordPress version.
     * @return  void
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     * @version 1.1.0
     */
    public function show_compatible( $version ) {
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
     * @since 1.1.0
     * @since 1.0.1
     * @version 1.1.0
     *
     * @param string $status The license status.
     */
    public function license_status_field( $status ) {
        $license_labels = Helper::license_status_types();

        echo empty( $status ) ? '' : esc_attr( $license_labels[ $status ] );
    }

    /**
     * Connect to the api server using API keys
     *
     * @return  boolean
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     * @version 1.1.0
     */
    public function connect() {
        $keys       = Helper::get_api_keys();
        $connection = $this->client->request( 'connect', $keys );

        Helper::log( 'Connecting...' );

        if ( $connection && $connection->connected && 'ok' === $connection->status ) {
            update_option( 'slswc_client_api_connected', apply_filters( 'slswc_api_connected', 'yes' ) );
            update_option( 'slswc_client_api_auth_user', apply_filters( 'slswc_api_auth_user', $connection->auth_user ) );

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
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     * @version 1.1.0
     */
    public function get_remote_product( $slug, $type = 'plugin' ) {
        $request_info = [
            'slug' => $slug,
            'type' => $type,
        ];

        $license_data = get_option( $slug . '_license_manager', null );

        if ( Helper::is_connected() ) {
            $request_info = array_merge( $request_info, Helper::get_api_keys() );
        } elseif ( null !== $license_data && ! empty( $license_data['license_key'] ) ) {
            $request_info['license_key'] = trim( $license_data['license_key'] );
        }

        $response = $this->client->request( 'product', $request_info );

        if ( is_object( $response ) && 'ok' === $response->status ) {
            return $response->product;
        }

        Helper::log( 'Get remote product' );
        Helper::log( $response->product );

        return [];
    }

    /**
     * Get a user's purchased products.
     *
     * @param   string $type The type of products. Expects plugins|themes, default 'plugins'.
     * @param   array  $args The arguments to form the query to search for the products.
     * @return  array
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     * @version 1.1.0
     */
    public function get_remote_products( $type = 'plugins', $args = [] ) {
        $licensed_products = [];
        $request_info      = [];
        $slugs             = [];

        $request_info['type'] = $type;

        $licenses_data = $this->get_license_data_for_all( $type );

        foreach ( $licenses_data as $slug => $_license_data ) {
            error_log( 'License data: ' . print_r( $_license_data, true ) );
            if ( empty( $_license_data ) ) {
                continue;
            }

            if ( ! $this->ignore_status( $_license_data['license_status'] ) ) {
                $_license_data['domain']    = untrailingslashit( str_ireplace( [ 'http://', 'https://' ], '', home_url() ) );
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
            [
                'post_name__in' => $slugs,
            ]
        );

        if ( Helper::is_connected() ) {
            $request_info['api_keys'] = Helper::get_api_keys();
        }

        $response = $this->client->request( 'products', $request_info );

        Helper::log( 'Getting remote products' );
        Helper::log( $response );

        if ( is_object( $response ) && 'ok' === $response->status ) {
            return $response->products;
        }

        return [];
    }

    /**
     * Get license data for all locally installed
     *
     * @param   string $type The type of products to return license details for. Expects plugins/themes, default empty.
     * @return  array
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     */
    public function get_license_data_for_all( $type = '' ) {
        global $slswc_updater;
        $all_products  = [];
        $licenses_data = [];

        if ( $this->valid_type( $type ) ) {
            $function              = "get_local_{$type}";
            $all_products[ $type ] = $slswc_updater->$function();
        } else {
            $all_products['themes']  = $slswc_updater->get_local_themes();
            $all_products['plugins'] = $slswc_updater->get_local_plugins();
        }

        foreach ( $all_products as $type => $_products ) {
            foreach ( $_products as $slug => $_product ) {
                $_license_data          = get_option( $slug . '_license_manager', [] );
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
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     */
    public function valid_type( $type ) {
        return in_array( $type, [ 'themes', 'plugins' ], true );
    }

    /**
     * Check if status should be ignored
     *
     * @param   string $status The status tp check.
     * @return  bool
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     */
    public function ignore_status( $status ) {
        $ignored_statuses = [ 'expired', 'max_activations', 'failed' ];
        return in_array( $status, $ignored_statuses, true );
    }

    /**
     * Get the current tab.
     *
     * @return  string
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     */
    public function get_tab() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
        return isset( $_GET['tab'] ) && ! empty( $_GET['tab'] )
            ? esc_attr( sanitize_text_field( wp_unslash( $_GET['tab'] ) ) )
            : 'licenses';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
    }

    /**
     * Save a list of products to the database.
     *
     * @param array $products List of products to save.
     * @return void
     */
    public function save_products( $products = [] ) {
        if ( empty( $products ) ) {
            $products = $this->$products;
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
    public function recursive_parse_args( $args, $defaults ) {
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
    public function get_messages() {
        return $this->messages;
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
    public function add_message( $key, $message, $type = 'success' ) {
        $this->messages[] = [
            'key'     => $key,
            'message' => $message,
            'type'    => $type,
        ];
    }

    /**
     * Display license update messages.
     *
     * @return void
     * @version 1.0.2
     * @since   1.0.2
     */
    public function display_messages() {
        if ( empty( $this->messages ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        foreach ( $this->messages as $message ) {
            echo sprintf(
                '<div class="%1$s notice is-dismissible"><p>%2$s</p></div>',
                esc_attr( $message['type'] ),
                wp_kses_post( $message['message'] )
            );
        }
    }

    /**
     * Get a list of plugins
     *
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     * @return array
     */
    public function get_plugins() {
        return $this->plugins;
    }

    /**
     * Set a list of all plugins
     *
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     * @return object
     */
    public function set_plugins( $plugins ) {
        $this->plugins = $plugins;

        return $this;
    }

    /**
     * Get a list of all themes
     *
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     * @return array
     */
    public function get_themes() {
        return $this->themes;
    }

    /**
     * Set a list of themes
     *
     * @version 1.1.0
     * @since   1.1.0 - Refactored into classes and converted into a composer package.
     * @return object
     */
    public function set_themes( $themes ) {
        $this->themes = $themes;

        return $this;
    }
}
