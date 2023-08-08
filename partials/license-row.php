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