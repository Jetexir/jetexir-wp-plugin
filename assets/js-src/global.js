wooAssistantAjax = false;

jQuery(document).ready(function ($) {
    function waModalOverlay(status, target = '') {
        let modalOverlay = $('#wa-modal-overlay'),
            modalTarget = modalOverlay.attr('data-wa-target');

        if (status && !modalOverlay.hasClass('active')) {
            modalOverlay.addClass('active').attr('data-wa-target', target);

        } else if (!status && modalOverlay.hasClass('active')) {
            modalOverlay.removeClass('active').removeAttr('data-wa-target');
            $(modalTarget).hide().removeClass('active').attr('aria-hidden', 'true');
        }
    }

    $('[data-wa-toggle="modal"]').on('click', function () {
        let $this = $(this),
            modalTarget = $this.data('wa-target');

        if (modalTarget !== undefined) {
            let modalTargetElm = $(modalTarget);
            if (modalTargetElm.length) {
                waModalOverlay(true, modalTarget);
                $(modalTarget).toggleClass('active').removeAttr('aria-hidden').show();
            }
        }
    });

    $('#wa-modal-overlay').on('click', function () {
        waModalOverlay(false);
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
            faqActive = faqItem.hasClass('active');

        $('.wa-faq-item').each(function (i) {
            $(this).removeClass('active');
            $(this).find('.wa-faq-answer').css('max-height', 0);
        });

        if (!faqActive) {
            faqItem.toggleClass('active');
            if (faqItem.hasClass('active')) {
                faqAnswer.css('max-height', 'initial');
            } else {
                faqAnswer.css('max-height', 0);
            }
        }
    });
});