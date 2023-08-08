<?php
/**
 * Show the api keys settings page
 *
 * @version     1.0.0
 */


use Madvault\Slswc\Client\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
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