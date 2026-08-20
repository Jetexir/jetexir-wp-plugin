/******/ (() => { // webpackBootstrap
/*!***********************************!*\
  !*** ./assets/js-src/fly-cart.js ***!
  \***********************************/
jQuery(document).ready(function ($) {
  let jetexirFlyCartChanged = false, jetexirFlyCartReloadTimer;

  const jetexirFlyCartUpdate = function (action = 'reload', productID = 0, itemKey = '', qty = 1) {
    let flyCartModal = $('#jetexir-fly-cart-modal'), flyCartModalBody = flyCartModal.find('.jetexir-modal-body'),
      flyCartLoader = flyCartModal.find('.jetexir-loader-wrap');

    if (jetexirAjax) return;
    jetexirAjax = true;

    flyCartLoader.css('display', '');
    if (action !== 'reload') jetexirFlyCartChanged = true;

    $.post(Jetexir.ajaxUrl, {
      nonce: Jetexir.ajaxNonce,
      action: "jetexir_fly_cart_update",
      cart_action: action,
      product_id: productID,
      item_key: itemKey,
      item_qty: qty
    })
      .done(function (data) {
        flyCartModalBody.html(data.data.cart);

        $('#jetexir-fly-icon-fly-cart').find('.jetexir-fly-icon-count').html(data.data.cart_items_count);

        jetexirFlyCartInit();

        if (data.data?.redirect && data.data.redirect !== '') window.location.href = data.data.redirect;
      })
      .fail(function (xhr, status, error) {
        if (xhr.responseJSON?.data?.refresh) setTimeout(function () {
          window.location.reload(true);
        }, 3000);
      })
      .always(function () {
        flyCartLoader.hide();
        jetexirAjax = false;
      });
  }, jetexirFlyCartInit = function () {
    $('.jetexir-fly-cart-item-quantity input.qty').unbind('change').on('change', function () {
      if (jetexirAjax) return;
      let $this = $(this), qtyVal = parseInt($this.val()), jetexirCartItem = $this.closest('.jetexir-fly-cart-item'),
        productID = jetexirCartItem.attr('data-product-id'), itemKey = jetexirCartItem.attr('data-item-key');
      qtyVal = isNaN(qtyVal) ? 1 : qtyVal;

      jetexirFlyCartUpdate('quantity', productID, itemKey, qtyVal);
    });

    $('.jetexir-fly-cart-item-remove').unbind('click').on('click', function (e) {
      e.preventDefault();
      if (jetexirAjax) return;
      let $this = $(this), jetexirCartItem = $this.closest('.jetexir-fly-cart-item'),
        productID = jetexirCartItem.attr('data-product-id'), itemKey = jetexirCartItem.attr('data-item-key');

      jetexirFlyCartUpdate('remove', productID, itemKey);
    });

    $('.jetexir-fly-cart-item-quantity button').unbind('click').on('click', function () {
      let qtyCurrentVal, parentDiv = $(this).closest('.jetexir-fly-cart-item-quantity'),
        jetexirQuantityInput = parentDiv.find('input.qty'), qtyVal = parseInt(jetexirQuantityInput.val()),
        step = parseInt(jetexirQuantityInput.attr('step')), min = parseInt(jetexirQuantityInput.attr('min')),
        max = parseInt(jetexirQuantityInput.attr('max')), action = $(this).data('action');

      step = isNaN(step) ? 1 : step;
      min = isNaN(min) ? 1 : min;
      qtyCurrentVal = qtyVal = isNaN(qtyVal) ? min : qtyVal;

      if (action === 'plus' && (isNaN(max) || qtyVal < max)) {
        qtyVal += step;

        if (!isNaN(max)) qtyVal = Math.min(qtyVal, max);

      } else if (action === 'minus' && qtyVal > min) {
        qtyVal -= step;
        qtyVal = Math.max(qtyVal, min);
      }

      if (qtyCurrentVal !== qtyVal) jetexirQuantityInput.val(qtyVal).trigger('change');
    });
  }

  window.addEventListener("jetexirModalClose", function (event) {
    if (jetexirFlyCartChanged && (Jetexir.pageName === 'cart' || Jetexir.pageName === 'checkout')) {
      window.location.reload(true);
    }
    jetexirFlyCartChanged = false;
  });

  //@TODO: Fly cart update, Need optimizing with some use-case
  $(document).on("ajaxComplete", function (event, request, settings) {
    if (!settings?.data.includes('jetexir_fly_cart')) {
      clearTimeout(jetexirFlyCartReloadTimer);
      jetexirFlyCartReloadTimer = setTimeout(function () {
        jetexirFlyCartUpdate();
      }, 1000);
    }
  });

  $('#jetexir-fly-icon-fly-cart').on('click', function (e) {
    e.preventDefault();
  });

  jetexirFlyCartInit();

  if (JetexirFlyCart.reloadOnLoad === '1') {
    setTimeout(function () {
      jetexirFlyCartUpdate();
    }, 2000);
  }
});

/******/ })()
;