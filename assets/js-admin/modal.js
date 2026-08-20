/******/ (() => { // webpackBootstrap
/*!**************************************!*\
  !*** ./assets/js-admin-src/modal.js ***!
  \**************************************/
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
});

/******/ })()
;