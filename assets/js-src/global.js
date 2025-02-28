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

    /* $('.wa-faqs-wrap .wa-faq-question').unbind("click").on('click', function (e) {
         $(this).closest('.wa-faq-item').toggleClass('active');
     });*/

    let question = document.querySelectorAll(".wa-faq-question");

    question.forEach(question => {
        question.addEventListener("click", event => {
            const active = document.querySelector(".wa-faq-question.active");
            if (active && active !== question) {
                active.classList.toggle("active");
                active.nextElementSibling.style.maxHeight = 0;
            }
            question.classList.toggle("active");
            const answer = question.nextElementSibling;
            if (question.classList.contains("active")) {
                answer.style.maxHeight = 'initial';
            } else {
                answer.style.maxHeight = 0;
            }
        })
    });
});