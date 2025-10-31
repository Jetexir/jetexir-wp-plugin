jQuery(document).ready(function ($) {
  let waFlyCartChanged = false, waFlyCartReloadTimer;

  const waFlyCartUpdate = function (action = 'reload', productID = 0, itemKey = '', qty = 1) {
      let flyCartModal = $('#asfowoo-fly-cart-modal'),
        flyCartModalBody = flyCartModal.find('.asfowoo-modal-body'),
        flyCartLoader = flyCartModal.find('.asfowoo-loader-wrap');

      if (assistantForWooCommerceAjax) return;
      assistantForWooCommerceAjax = true;

      flyCartLoader.css('display', '');
      if (action !== 'reload')
        waFlyCartChanged = true;

      $.post(
        AssistantForWooCommerce.ajaxUrl,
        {
          nonce: AssistantForWooCommerce.ajaxNonce,
          action: "assistant_for_woocommerce_fly_cart_update",
          cart_action: action,
          product_id: productID,
          item_key: itemKey,
          item_qty: qty
        }
      )
        .done(function (data) {
          flyCartModalBody.html(data.data.cart);

          $('#asfowoo-fly-icon-fly-cart').find('.asfowoo-fly-icon-count').html(data.data.cart_items_count);

          waFlyCartInit();

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
          flyCartLoader.hide();
          assistantForWooCommerceAjax = false;
        });
    },
    waFlyCartInit = function () {
      $('.asfowoo-fly-cart-item-quantity input.qty').unbind('change').on('change', function () {
        if (assistantForWooCommerceAjax) return;
        let $this = $(this),
          qtyVal = parseInt($this.val()),
          waCartItem = $this.closest('.asfowoo-fly-cart-item'),
          productID = waCartItem.attr('data-product-id'),
          itemKey = waCartItem.attr('data-item-key');
        qtyVal = isNaN(qtyVal) ? 1 : qtyVal;

        waFlyCartUpdate('quantity', productID, itemKey, qtyVal);
      });

      $('.asfowoo-fly-cart-item-remove').unbind('click').on('click', function (e) {
        e.preventDefault();
        if (assistantForWooCommerceAjax) return;
        let $this = $(this),
          waCartItem = $this.closest('.asfowoo-fly-cart-item'),
          productID = waCartItem.attr('data-product-id'),
          itemKey = waCartItem.attr('data-item-key');

        waFlyCartUpdate('remove', productID, itemKey);
      });

      $('.asfowoo-fly-cart-item-quantity button').unbind('click').on('click', function () {
        let qtyCurrentVal,
          parentDiv = $(this).closest('.asfowoo-fly-cart-item-quantity'),
          waQuantityInput = parentDiv.find('input.qty'),
          qtyVal = parseInt(waQuantityInput.val()),
          step = parseInt(waQuantityInput.attr('step')),
          min = parseInt(waQuantityInput.attr('min')),
          max = parseInt(waQuantityInput.attr('max')),
          action = $(this).data('action');

        step = isNaN(step) ? 1 : step;
        min = isNaN(min) ? 1 : min;
        qtyCurrentVal = qtyVal = isNaN(qtyVal) ? min : qtyVal;

        if (action === 'plus' && (isNaN(max) || qtyVal < max)) {
          qtyVal += step;

          if (!isNaN(max))
            qtyVal = Math.min(qtyVal, max);

        } else if (action === 'minus' && qtyVal > min) {
          qtyVal -= step;
          qtyVal = Math.max(qtyVal, min);
        }

        if (qtyCurrentVal !== qtyVal)
          waQuantityInput.val(qtyVal).trigger('change');
      });
    }

  window.addEventListener("waModalClose", function (event) {
    if (waFlyCartChanged && (AssistantForWooCommerce.pageName === 'cart' || AssistantForWooCommerce.pageName === 'checkout')) {
      window.location.reload(true);
    }
    waFlyCartChanged = false;
  });

  //@TODO: Fly cart update, Need optimizing with some use-case
  $(document).on("ajaxComplete", function (event, request, settings) {
    if (!settings?.data.includes('assistant_for_woocommerce_fly_cart')) {
      clearTimeout(waFlyCartReloadTimer);
      waFlyCartReloadTimer = setTimeout(function () {
        waFlyCartUpdate();
      }, 1000);
    }
  });

  $('#asfowoo-fly-icon-fly-cart').on('click', function (e) {
    e.preventDefault();
  });

  waFlyCartInit();

  if (AssistantForWooCommerceFlyCart.reloadOnLoad === '1') {
    setTimeout(function () {
      waFlyCartUpdate();
    }, 2000);
  }
});
