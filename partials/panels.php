<?php
/**
 * The main panel for the updater.
 *
 * @version 1.0.0
 * @since  1.0.0
 *
 * @package SLSWC_Updater/Partials
 */

?>
<div class="wp-filter">
    <ul class="filter-links">
        <li>
            <a href="<?php echo esc_url_raw( $license_admin_url ); ?>&tab=licenses"
                class="<?php echo esc_attr( ( 'licenses' === $tab || empty( $tab ) ) ? 'current' : '' ); ?>">
                <?php esc_attr_e( 'Licenses', 'slswc-client' ); ?>
            </a>
        </li>
        <li>
            <a href="<?php echo esc_url_raw( $license_admin_url ); ?>&tab=plugins"
                class="<?php echo ( 'plugins' === $tab ) ? 'current' : ''; ?>">
                <?php esc_attr_e( 'Plugins', 'slswc-client' ); ?>
            </a>
        </li>
        <li>
            <a href="<?php echo esc_url_raw( $license_admin_url ); ?>&tab=themes"
                class="<?php echo ( 'themes' === $tab ) ? 'current' : ''; ?>">
                <?php esc_attr_e( 'Themes', 'slswc-client' ); ?>
            </a>
        </li>
        <li>
            <a href="<?php echo esc_url_raw( $license_admin_url ); ?>&tab=api"
                class="<?php echo ( 'api' === $tab ) ? 'current' : ''; ?>">
                <?php esc_attr_e( 'API', 'slswc-client' ); ?>
            </a>
        </li>
    </ul>
</div>
<br class="clear" />

<div class="tablenav-top"></div>
<?php if ( 'licenses' === $tab || empty( $tab ) ): ?>
<div id="licenses">
    <?php $this->licenses_form(); ?>
</div>

<?php elseif ( 'plugins' === $tab ): ?>
<div id="plugins" class="wp-list-table widefat plugin-install">
    <?php $this->list_products( $this->plugins ); ?>
</div>

<?php elseif ( 'themes' === $tab ): ?>
<div id="themes" class="wp-list-table widefat plugin-install">
    <?php $this->list_products( $this->themes ); ?>
</div>

<?php else: ?>
<div id="api">
    <?php $this->api_form(); ?>
</div>
    <?php
endif;
