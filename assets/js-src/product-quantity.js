jQuery(document).ready(function ($) {
    const waWcCart = $('form[class="cart"]'),
        waQuantityInput = $('input[name="quantity"]');

    if (waWcCart.length > 0) {
        waWcCart.addClass('wa-quantity-input-plus-minus')
    }

    if (waQuantityInput.length > 0) {
        let qtyType = waQuantityInput.attr('type');
        if (qtyType !== 'text')
            waQuantityInput.attr('type', 'text');

        $('.wa-button-change-quantity').on('click', function () {
            let qtyVal = parseInt(waQuantityInput.val()),
                step = parseInt(waQuantityInput.attr('step')),
                min = parseInt(waQuantityInput.attr('min')),
                max = parseInt(waQuantityInput.attr('max')),
                action = $(this).data('action');

            step = isNaN(step) ? 1 : step;
            min = isNaN(min) ? 1 : min;

            if (action === 'plus' && (isNaN(max) || qtyVal < max))
                qtyVal += step;
            else if (action === 'minus' && qtyVal > min)
                qtyVal -= step;

            waQuantityInput.val(qtyVal);
        });
    }
});