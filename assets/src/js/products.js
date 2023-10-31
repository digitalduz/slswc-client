jQuery(function ($) {
  'use strict';

  $(document).ready(function () {
    slswcClient.init();
  });

  const slswcClient = {
    init: function () {
      slswcClient.installer();
    },
    installer: function () {
      if (!$('.slswc-install-now') && !$('.slswc-update-now')) {
        return;
      }

      $('.slswc-install-now, .slswc-update-now').on('click', function (e) {
        e.preventDefault();
        let $el = $(this);
        let download_url = $(this).data('download_url');
        let name = $(this).data('name');
        let slug = $(this).data('slug');
        let type = $(this).data('type');
        let label = $(this).html();
        let nonce = $(this).data('nonce');

        let action_label = window.slswc_updater_licenses.processing_label;
        $(this).html(
          `<img src="${window.slswc_updater_licenses.loaderUrl}" /> ` +
            action_label
        );
        $.ajax({
          url: window.ajaxUrl,
          data: {
            action: 'slswc_install_product',
            download_url: download_url,
            name: name,
            slug: slug,
            type: type,
            nonce: nonce,
          },
          dataType: 'json',
          type: 'POST',
          success: function (response) {
            if (response.success) {
              $('#slswc-product-install-message p').html(response.data.message);
              $('#slswc-product-install-message').addClass('updated').show();
            } else {
              $('#slswc-product-install-message p').html(response.data.message);
              $('#slswc-product-install-message')
                .addClass('notice-warning')
                .show();
            }
            $el.html(slswc_updater_licenses.done_label);
            $el.attr('disabled', 'disabled');
          },
          error: function (error) {
            $('#slswc-product-install-message p').html(error.data.message);
            $('#slswc-product-install-message').addClass('notice-error').show();
          },
        });
      });
    },
  };
});
