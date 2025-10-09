jQuery(document).ready(function ($) {
    $('.asfowoo-product-compare-button').on('click', function (e) {
        var $this = $(this);
        if (assistantForWooCommerceAjax) return;
        assistantForWooCommerceAjax = true;

        $.post(
            AssistantForWooCommerce.ajaxUrl,
            {
                nonce: AssistantForWooCommerce.ajaxNonce,
                action: "asfowoo_product_compare_add_remove",
                product_id: $this.data('id')
            }
        )
            .done(function (data) {
                if (data.data.status === 'added')
                    $this.addClass('asfowoo-button-remove');
                else if (data.data.status === 'removed')
                    $this.removeClass('asfowoo-button-remove');
                else if (data.data.status === 'max_exceeded')
                    alert(AssistantForWooCommerceProductCompare.maxExceededMessage.replace('%number%', data.data.count));

                if ($this.data('action') === 'refresh')
                    window.location.reload(true);

                if (data.data?.redirect && data.data.redirect !== '')
                    window.location.href = data.data.redirect;
            })
            .fail(function (xhr, status, error) {
                if (xhr.responseJSON?.data?.refresh)
                    setTimeout(function () {
                        window.location.reload(true);
                    }, 3000);
            })
            .always(function () {
                assistantForWooCommerceAjax = false;
            });
    });
});