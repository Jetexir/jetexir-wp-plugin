/******/ (() => { // webpackBootstrap
/*!*******************************************!*\
  !*** ./assets/js-src/product-quantity.js ***!
  \*******************************************/
jQuery(document).ready(function ($) {
  const jetexirWcCart = $('form.cart');

  if (jetexirWcCart.length > 0) {
    jetexirWcCart.addClass('jetexir-quantity-input-plus-minus');
    jetexirWcCart.addClass('jetexir-appearance-text-field');
  }

  if (JetexirProductQuantity.plusMinusButtons === '1') {
    $('.jetexir-button-change-quantity').on('click', function () {
      let qtyCurrentVal, parentDiv = $(this).closest('.quantity'),
        jetexirQuantityInput = parentDiv.find('input[name="quantity"]'), qtyVal = parseInt(jetexirQuantityInput.val()),
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

  setTimeout(function () {
    if (JetexirProductQuantity.quantityDisabled === '1') {
      jetexirWcCart.find('input[name="quantity"]').prop('disabled', true);
      $('.wc-block-components-quantity-selector__input').prop('disabled', true);
      $('.wc-block-components-quantity-selector__button').prop('disabled', true);
    }
  }, 1000);
});

/******/ })()
;