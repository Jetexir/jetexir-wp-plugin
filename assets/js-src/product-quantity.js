jQuery(document).ready(function ($) {
    const waWcCart = $('form.cart'),
        waQuantityInput = $('input[name="quantity"]');

    if (waWcCart.length > 0) {
        waWcCart.addClass('wa-quantity-input-plus-minus');
        waWcCart.addClass('wa-appearance-text-field');
    }

    if (waQuantityInput.length > 0) {
        $('.wa-button-change-quantity').on('click', function () {
            let qtyVal = parseInt(waQuantityInput.val()),
                step = parseInt(waQuantityInput.attr('step')),
                min = parseInt(waQuantityInput.attr('min')),
                max = parseInt(waQuantityInput.attr('max')),
                action = $(this).data('action');

            step = isNaN(step) ? 1 : step;
            min = isNaN(min) ? 1 : min;

            if (action === 'plus' && (isNaN(max) || qtyVal < max)) {
                qtyVal += step;

                if (!isNaN(max))
                    qtyVal = Math.min(qtyVal, max);

            } else if (action === 'minus' && qtyVal > min) {
                qtyVal -= step;
                qtyVal = Math.max(qtyVal, min);
            }

            waQuantityInput.val(qtyVal);
        });
    }
});