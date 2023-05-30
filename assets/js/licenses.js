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