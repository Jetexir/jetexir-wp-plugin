jQuery(document).ready(function ($) {
    var waBody = $('body');

    wooAssistantModalCloseEvent = new CustomEvent(
        "waModalClose",
        {
            detail: {
                time: new Date(),
            },
            bubbles: true,
            cancelable: true
        }
    );

    function waToggleModal(status, target = '') {
        let modalOverlay = $('#wa-modal-overlay'),
            modalTarget = waBody.attr('data-wa-modal-target');

        if (status && !waBody.hasClass('wa-modal-open')) {
            waBody.css({
                "overflow": "hidden",
                "padding-right": "0"
            })
                .addClass('wa-modal-open')
                .attr('data-wa-modal-target', target);
            $(target).toggleClass('wa-active').removeAttr('aria-hidden').show();
            if (modalOverlay !== undefined)
                modalOverlay.addClass('wa-active');

        } else if (!status && waBody.hasClass('wa-modal-open')) {
            window.dispatchEvent(wooAssistantModalCloseEvent);

            waBody.css({
                "overflow": "",
                "padding-right": ""
            })
                .removeClass('wa-modal-open')
                .removeAttr('data-wa-modal-target');
            $(modalTarget).hide().removeClass('wa-active').attr('aria-hidden', 'true');
            if (modalOverlay !== undefined)
                modalOverlay.removeClass('wa-active');
        }
    }

    $('[data-wa-toggle="modal"]').on('click', function () {
        let $this = $(this),
            modalTarget = $this.data('wa-target');

        if (modalTarget !== undefined) {
            let modalTargetElm = $(modalTarget);
            if (modalTargetElm.length) {
                waToggleModal(true, modalTarget);
            }
        }
    });

    $('#wa-modal-overlay, [data-wa-dismiss="modal"]').on('click', function () {
        waToggleModal(false);
    });
});