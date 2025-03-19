wooAssistantAjax = false;

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

    $('.wa-copy-text').on('click', function (e) {
        e.preventDefault();

        let copyText = $(this).data('copy'), copyIcon;
        if (copyText !== undefined) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(copyText);

                if ((copyIcon = $(this).find('.wa-icon-file_copy')).length > 0) {
                    copyIcon.removeClass('wa-icon-file_copy').addClass('wa-icon-content_copy');

                    setTimeout(function () {
                        copyIcon.removeClass('wa-icon-content_copy').addClass('wa-icon-file_copy');
                    }, 500);
                }
            } else {
                alert(WooAssistant.sslError);
            }
        }
    });

    $('.wa-faq-question').on('click', function () {
        let $this = $(this),
            faqItem = $this.closest('.wa-faq-item'),
            faqAnswer = faqItem.find('.wa-faq-answer'),
            faqActive = faqItem.hasClass('wa-active');

        $('.wa-faq-item').each(function (i) {
            $(this).removeClass('wa-active');
            $(this).find('.wa-faq-answer').css('max-height', 0);
        });

        if (!faqActive) {
            faqItem.toggleClass('wa-active');
            if (faqItem.hasClass('wa-active')) {
                faqAnswer.css('max-height', 'initial');
            } else {
                faqAnswer.css('max-height', 0);
            }
        }
    });

    $('a[data-wa-product-remove-action]').on('click', function (e) {
        e.preventDefault();

        let $this = $(this),
            removeAction = $this.data('wa-product-remove-action'),
            removeWrapperTarget = $this.data('wa-product-list-wrap'),
            removeNoticeTarget = $this.data('wa-product-list-notice'),
            removeParentTarget = $this.data('wa-product-parent'),
            removeLoaderTarget = $this.data('wa-product-loader'),
            removeProductID = $this.data('product-id'),
            removeWrapper, removeNotice, removeParent, removeLoader;

        if (removeProductID === undefined || removeProductID === '' || removeAction === undefined || removeAction === '')
            return;

        removeWrapperTarget = removeWrapperTarget === undefined || removeWrapperTarget === '' ? '.wa-product-list-wrap' : removeWrapperTarget;
        removeNoticeTarget = removeNoticeTarget === undefined || removeNoticeTarget === '' ? '.wa-product-list-notice' : removeNoticeTarget;
        removeParentTarget = removeParentTarget === undefined || removeParentTarget === '' ? '.wa-product-item-wrap' : removeParentTarget;
        removeLoaderTarget = removeLoaderTarget === undefined || removeLoaderTarget === '' ? '.wa-loader-wrap' : removeLoaderTarget;

        removeWrapper = $this.closest(removeWrapperTarget);
        removeNotice = removeWrapper.find(removeNoticeTarget);
        removeParent = $this.closest(removeParentTarget);
        removeLoader = removeWrapper.find(removeLoaderTarget);

        if (wooAssistantAjax) return;
        wooAssistantAjax = true;

        removeLoader.css('display', '');

        $.post(
            WooAssistant.ajaxUrl,
            {
                nonce: WooAssistant.ajaxNonce,
                action: removeAction,
                product_id: removeProductID,
            }
        )
            .done(function (data) {
                removeParent.slideUp("normal", function () {
                    $(this).remove();

                    if (removeWrapper.find(removeParentTarget).length === 0) {
                        removeNotice.css('display', '');
                    }
                });

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
                removeLoader.hide();
                wooAssistantAjax = false;
            });
    })
});