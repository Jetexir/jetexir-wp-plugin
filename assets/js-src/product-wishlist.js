jQuery(document).ready(function ($) {
    $('.wa-product-wishlist-button').on('click', function (e) {
        e.preventDefault();

        var $this = $(this),
            waWishlist = $this.attr('data-in-wishlist'),
            waWishlistAddedAction = $this.attr('data-added-action');

        if (waWishlist !== '' && waWishlistAddedAction === 'open_page') {
            window.location.href = WooAssistantProductWishlist.wishlistPage;
            return;
        }

        if (wooAssistantAjax) return;
        wooAssistantAjax = true;

        $.post(
            WooAssistant.ajaxUrl,
            {
                nonce: WooAssistant.ajaxNonce,
                action: "wa_product_wishlist_add_remove",
                product_id: $this.data('product-id')
            }
        )
            .done(function (data) {
                if (data.data.status === 'added') {
                    $this.attr('data-in-wishlist', data.data.list);
                    $this.addClass('wa-remove-action');
                    $this.html($this.data('added-text'));

                } else if (data.data.status === 'removed') {
                    $this.attr('data-in-wishlist', '');
                    $this.removeClass('wa-remove-action');
                    $this.html($this.data('add-text'));

                } else if (data.data.status === 'max_exceeded')
                    alert(WooAssistantProductWishlist.maxExceededMessage.replace('%number%', data.data.count));

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
                wooAssistantAjax = false;
            });
    })
});