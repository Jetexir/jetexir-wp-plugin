wooAssistantAjax = false;

jQuery(document).ready(function ($) {
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
});