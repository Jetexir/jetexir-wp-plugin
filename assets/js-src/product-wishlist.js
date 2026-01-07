jQuery(document).ready(function ($) {
  $('.jetexir-product-wishlist-button').on('click', function (e) {
    e.preventDefault();

    var $this = $(this),
      waWishlist = $this.attr('data-in-wishlist'),
      waWishlistAddedAction = $this.attr('data-added-action');

    if (waWishlist !== '' && waWishlistAddedAction === 'open_page') {
      window.location.href = JetexirProductWishlist.wishlistPage;
      return;
    }

    if (jetexirAjax) return;
    jetexirAjax = true;

    $.post(
      Jetexir.ajaxUrl,
      {
        nonce: Jetexir.ajaxNonce,
        action: "jetexir_product_wishlist_add_remove",
        product_id: $this.data('product-id')
      }
    )
      .done(function (data) {
        if (data.data.status === 'added') {
          $this.attr('data-in-wishlist', data.data.list);
          $this.addClass('jetexir-remove-action');
          $this.html($this.data('added-text'));

        } else if (data.data.status === 'removed') {
          $this.attr('data-in-wishlist', '');
          $this.removeClass('jetexir-remove-action');
          $this.html($this.data('add-text'));

        } else if (data.data.status === 'max_exceeded')
          alert(JetexirProductWishlist.maxExceededMessage.replace('%number%', data.data.count));

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
        jetexirAjax = false;
      });
  })
});
