/******/ (() => { // webpackBootstrap
/*!*********************************!*\
  !*** ./assets/js-src/global.js ***!
  \*********************************/
jetexirAjax = false;

jQuery(document).ready(function ($) {
  var jetexirBody = $('body');

  jetexirModalCloseEvent = new CustomEvent("jetexirModalClose", {
    detail: {
      time: new Date(),
    }, bubbles: true, cancelable: true
  });

  function jetexirToggleModal(status, target = '') {
    let modalOverlay = $('#jetexir-modal-overlay'), modalTarget = jetexirBody.attr('data-jetexir-modal-target');

    if (status && !jetexirBody.hasClass('jetexir-modal-open')) {
      jetexirBody.css({
        "overflow": "hidden", "padding-right": "0"
      })
        .addClass('jetexir-modal-open')
        .attr('data-jetexir-modal-target', target);
      $(target).toggleClass('jetexir-active').removeAttr('aria-hidden').show();
      if (modalOverlay !== undefined) modalOverlay.addClass('jetexir-active');

    } else if (!status && jetexirBody.hasClass('jetexir-modal-open')) {
      window.dispatchEvent(jetexirModalCloseEvent);

      jetexirBody.css({
        "overflow": "", "padding-right": ""
      })
        .removeClass('jetexir-modal-open')
        .removeAttr('data-jetexir-modal-target');
      $(modalTarget).hide().removeClass('jetexir-active').attr('aria-hidden', 'true');
      if (modalOverlay !== undefined) modalOverlay.removeClass('jetexir-active');
    }
  }

  $('[data-jetexir-toggle="modal"]').on('click', function () {
    let $this = $(this), modalTarget = $this.data('jetexir-target');

    if (modalTarget !== undefined) {
      let modalTargetElm = $(modalTarget);
      if (modalTargetElm.length) {
        jetexirToggleModal(true, modalTarget);
      }
    }
  });

  $('#jetexir-modal-overlay, [data-jetexir-dismiss="modal"]').on('click', function () {
    jetexirToggleModal(false);
  });

  $('.jetexir-copy-text').on('click', function (e) {
    e.preventDefault();

    let copyText = $(this).data('copy'), copyIcon;
    if (copyText !== undefined) {
      if (navigator.clipboard) {
        navigator.clipboard.writeText(copyText);

        if ((copyIcon = $(this).find('.jetexir-icon-file_copy')).length > 0) {
          copyIcon.removeClass('jetexir-icon-file_copy').addClass('jetexir-icon-content_copy');

          setTimeout(function () {
            copyIcon.removeClass('jetexir-icon-content_copy').addClass('jetexir-icon-file_copy');
          }, 500);
        }
      } else {
        alert(Jetexir.sslError);
      }
    }
  });

  $('.jetexir-faq-question').on('click', function () {
    let $this = $(this), faqItem = $this.closest('.jetexir-faq-item'), faqAnswer = faqItem.find('.jetexir-faq-answer'),
      faqActive = faqItem.hasClass('jetexir-active');

    $('.jetexir-faq-item').each(function (i) {
      $(this).removeClass('jetexir-active');
      $(this).find('.jetexir-faq-answer').css('max-height', 0);
    });

    if (!faqActive) {
      faqItem.toggleClass('jetexir-active');
      if (faqItem.hasClass('jetexir-active')) {
        faqAnswer.css('max-height', 'initial');
      } else {
        faqAnswer.css('max-height', 0);
      }
    }
  });

  $('a[data-jetexir-product-remove-action]').on('click', function (e) {
    e.preventDefault();

    let $this = $(this), removeAction = $this.data('jetexir-product-remove-action'),
      removeWrapperTarget = $this.data('jetexir-product-list-wrap'),
      removeNoticeTarget = $this.data('jetexir-product-list-notice'),
      removeParentTarget = $this.data('jetexir-product-parent'),
      removeLoaderTarget = $this.data('jetexir-product-loader'), removeProductID = $this.data('product-id'),
      removeWrapper, removeNotice, removeParent, removeLoader;

    if (removeProductID === undefined || removeProductID === '' || removeAction === undefined || removeAction === '') return;

    removeWrapperTarget = removeWrapperTarget === undefined || removeWrapperTarget === '' ? '.jetexir-product-list-wrap' : removeWrapperTarget;
    removeNoticeTarget = removeNoticeTarget === undefined || removeNoticeTarget === '' ? '.jetexir-product-list-notice' : removeNoticeTarget;
    removeParentTarget = removeParentTarget === undefined || removeParentTarget === '' ? '.jetexir-product-item-wrap' : removeParentTarget;
    removeLoaderTarget = removeLoaderTarget === undefined || removeLoaderTarget === '' ? '.jetexir-loader-wrap' : removeLoaderTarget;

    removeWrapper = $this.closest(removeWrapperTarget);
    removeNotice = removeWrapper.find(removeNoticeTarget);
    removeParent = $this.closest(removeParentTarget);
    removeLoader = removeWrapper.find(removeLoaderTarget);

    if (jetexirAjax) return;
    jetexirAjax = true;

    removeLoader.css('display', '');

    $.post(Jetexir.ajaxUrl, {
      nonce: Jetexir.ajaxNonce, action: removeAction, product_id: removeProductID,
    })
      .done(function (data) {
        removeParent.slideUp("normal", function () {
          $(this).remove();

          if (removeWrapper.find(removeParentTarget).length === 0) {
            removeNotice.css('display', '');
          }
        });

        if (data.data?.redirect && data.data.redirect !== '') window.location.href = data.data.redirect;
      })
      .fail(function (xhr, status, error) {
        if (xhr.responseJSON?.data?.refresh) setTimeout(function () {
          window.location.reload(true);
        }, 3000);
      })
      .always(function () {
        removeLoader.hide();
        jetexirAjax = false;
      });
  })
});

/******/ })()
;