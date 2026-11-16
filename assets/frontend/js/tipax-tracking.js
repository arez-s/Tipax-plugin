jQuery(function ($) {
    if (typeof tipaxTrackingConfig === 'undefined') {
        return;
    }

    function buildFallbackError(message) {
        return '' +
            '<article class="tipax-tracking-card is-error">' +
                '<div class="tipax-tracking-card-head">' +
                    '<div>' +
                        '<span class="tipax-tracking-status-label">خطا در رهگیری</span>' +
                        '<h3>رهگیری مرسوله انجام نشد</h3>' +
                    '</div>' +
                    '<div class="tipax-tracking-status-pill is-error">ناموفق</div>' +
                '</div>' +
                '<ul class="tipax-tracking-error-list"><li>' + message + '</li></ul>' +
            '</article>';
    }

    $(document).on('submit', '[data-tipax-tracking-form]', function (event) {
        event.preventDefault();

        var form = $(this);
        var widget = form.closest('[data-tipax-tracking-widget]');
        var button = form.find('[data-tipax-submit]');
        var result = widget.find('[data-tipax-tracking-result]');
        var input = form.find('input[name="tracking_code"]');
        var lookupCode = $.trim(input.val() || '').toLowerCase();
        var defaultButtonText = button.data('default-text') || button.text();

        button.data('default-text', defaultButtonText);
        input.val(lookupCode);

        if (!lookupCode) {
            result.html(buildFallbackError('کد رهگیری یا کد قرارداد را وارد کنید.'));
            input.trigger('focus');
            return;
        }

        widget.addClass('is-loading');
        button.prop('disabled', true).text(tipaxTrackingConfig.loadingText);
        result.html(
            '<div class="tipax-tracking-loading">' +
                '<span></span>' +
                '<p>' + tipaxTrackingConfig.loadingText + '</p>' +
            '</div>'
        );

        $.post(tipaxTrackingConfig.ajaxUrl, {
            action: tipaxTrackingConfig.action,
            nonce: tipaxTrackingConfig.nonce,
            tracking_code: lookupCode
        }).done(function (response) {
            if (response && response.data && response.data.html) {
                result.html(response.data.html);
                return;
            }

            result.html(buildFallbackError(tipaxTrackingConfig.unexpectedErrorText));
        }).fail(function (xhr) {
            if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.html) {
                result.html(xhr.responseJSON.data.html);
                return;
            }

            if (xhr && xhr.status === 429) {
                result.html(buildFallbackError(tipaxTrackingConfig.rateLimitText));
                return;
            }

            result.html(buildFallbackError(tipaxTrackingConfig.unexpectedErrorText));
        }).always(function () {
            widget.removeClass('is-loading');
            button.prop('disabled', false).text(defaultButtonText);
        });
    });
});
