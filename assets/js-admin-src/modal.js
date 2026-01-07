jQuery(document).ready(function ($) {
  var waBody = $('body');

  jetexirModalCloseEvent = new CustomEvent(
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
    let modalOverlay = $('#jetexir-modal-overlay'),
      modalTarget = waBody.attr('data-jetexir-modal-target');

    if (status && !waBody.hasClass('jetexir-modal-open')) {
      waBody.css({
        "overflow": "hidden",
        "padding-right": "0"
      })
        .addClass('jetexir-modal-open')
        .attr('data-jetexir-modal-target', target);
      $(target).toggleClass('jetexir-active').removeAttr('aria-hidden').show();
      if (modalOverlay !== undefined)
        modalOverlay.addClass('jetexir-active');

    } else if (!status && waBody.hasClass('jetexir-modal-open')) {
      window.dispatchEvent(jetexirModalCloseEvent);

      waBody.css({
        "overflow": "",
        "padding-right": ""
      })
        .removeClass('jetexir-modal-open')
        .removeAttr('data-jetexir-modal-target');
      $(modalTarget).hide().removeClass('jetexir-active').attr('aria-hidden', 'true');
      if (modalOverlay !== undefined)
        modalOverlay.removeClass('jetexir-active');
    }
  }

  $('[data-jetexir-toggle="modal"]').on('click', function () {
    let $this = $(this),
      modalTarget = $this.data('jetexir-target');

    if (modalTarget !== undefined) {
      let modalTargetElm = $(modalTarget);
      if (modalTargetElm.length) {
        waToggleModal(true, modalTarget);
      }
    }
  });

  $('#jetexir-modal-overlay, [data-jetexir-dismiss="modal"]').on('click', function () {
    waToggleModal(false);
  });
});
