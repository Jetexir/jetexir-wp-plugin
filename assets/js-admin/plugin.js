/******/ (() => { // webpackBootstrap
/*!***************************************!*\
  !*** ./assets/js-admin-src/plugin.js ***!
  \***************************************/
jQuery(document).ready(function ($) {
  let jetexirWpColorPickerPalettes = ['#333', '#5de0f0', '#608bf7', '#7fff3f', '#00b700', '#fff200', '#ffae63', '#e64f6f', '#ef32e3', '#d1c1ff', '#873eff'],
    jetexirWpColorPickerOptions = {
      defaultColor: false, change: function (event, ui) {
        jetexirActiveSettingsForm();
      }, clear: function () {
        jetexirActiveSettingsForm();
      }, hide: true, palettes: jetexirWpColorPickerPalettes
    }, jetexirSettingsSubmitActive = false, jetexirWpMediaFrames = {};

  const jetexirBody = $('body'), jetexirContentWrap = $('#jetexir-content-wrap'),
    jetexirSettingsHeader = $('#jetexir-settings-header'), jetexirSettingsSidebar = $('#jetexir-sidebar'),
    jetexirSettingsDisplaySidebar = $('#jetexir-display-sidebar'),
    jetexirSettingsHideSidebar = $('#jetexir-hide-sidebar'),
    jetexirSettingsSectionLinks = $('.jetexir-section-links ul'),
    jetexirSettingsForm = document.getElementById('jetexir-settings-form'),
    jetexirSettingsFooter = document.getElementById('jetexir-settings-footer'),
    jetexirSettingsResetButton = document.getElementById("jetexir-settings-reset-button");

  let jetexirContentWrapPrevScrollPos = jetexirContentWrap.scrollTop(),
    jetexirContentWrapCurrentScrollPos = jetexirContentWrapPrevScrollPos,
    jetexirPageRefreshedAfter = parseInt(Jetexir.pageRefreshedAfter);

  /**
   * Page refresh
   * */
  if (jetexirPageRefreshedAfter > 0) {
    setTimeout(function () {
      if (Jetexir.pageRefreshUrl !== null) window.location.href = Jetexir.pageRefreshUrl; else window.location.reload(true);
    }, jetexirPageRefreshedAfter);
  }

  /**
   * Hide header on scroll down and sticky on scroll to top
   * */
  jetexirContentWrap.scroll(function () {
    jetexirContentWrapCurrentScrollPos = $(this).scrollTop();
    if (jetexirContentWrapPrevScrollPos < jetexirContentWrapCurrentScrollPos && jetexirContentWrapCurrentScrollPos > jetexirSettingsHeader.outerHeight()) jetexirSettingsHeader.addClass('hide-header'); else jetexirSettingsHeader.removeClass('hide-header');

    jetexirContentWrapPrevScrollPos = jetexirContentWrapCurrentScrollPos;
  });

  /**
   * Sidebar menu
   * */
  jetexirSettingsDisplaySidebar.on('click', function (e) {
    e.preventDefault();
    jetexirSettingsSidebar.addClass('jetexir-mobile-sidebar');
    jetexirBody.addClass('jetexir-mobile-sidebar-active');
  });
  jetexirSettingsHideSidebar.on('click', function (e) {
    e.preventDefault();
    jetexirSettingsSidebar.removeClass('jetexir-mobile-sidebar');
    jetexirBody.removeClass('jetexir-mobile-sidebar-active');
  });

  /**
   * Auto scroll to active section link
   * */
  if (jetexirSettingsSectionLinks.length) {
    let jetexirSectionActiveLink = jetexirSettingsSectionLinks.find('.jetexir-section-link-current'),
      jetexirSectionOutsideActiveLink = jetexirSettingsSectionLinks.outerWidth() - 100 < jetexirSectionActiveLink.position().left,
      jetexirSectionScrollActiveLink = jetexirSectionActiveLink.position().left - jetexirSectionActiveLink.outerWidth(true) - (jetexirSettingsSectionLinks.outerWidth() / 3);

    if (isRtl) {
      jetexirSectionOutsideActiveLink = jetexirSectionActiveLink.position().left - 100 < 0;
    }

    if (jetexirSectionOutsideActiveLink) {
      jetexirSettingsSectionLinks.animate({
        scrollLeft: jetexirSectionScrollActiveLink
      }, 500);
    }
  }

  /**
   * Modal methods
   * */
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

  function jetexirModalInit(wrapper) {
    wrapper.find('[data-jetexir-toggle="modal"]').unbind('click').on('click', function () {
      let $this = $(this), modalTarget = $this.data('jetexir-target');

      if (modalTarget !== undefined) {
        let modalTargetElm = $(modalTarget);
        if (modalTargetElm.length) {
          jetexirToggleModal(true, modalTarget);
        }
      }
    });
  }

  jetexirModalInit(jetexirBody);
  $('#jetexir-modal-overlay, [data-jetexir-dismiss="modal"]').on('click', function () {
    jetexirToggleModal(false);
  });


  function jetexirActiveSettingsForm() {
    if (jetexirSettingsSubmitActive) return;
    jetexirSettingsSubmitActive = true;

    if (jetexirSettingsFooter) jetexirSettingsFooter.classList.remove('jetexir-submit-inactive');

    window.addEventListener("beforeunload", jetexirSettingsFormChangeAlert);
  }

  const jetexirSettingsFormChangeAlert = (event) => {
    event.preventDefault();
    event.returnValue = true;
  }

  if (jetexirSettingsForm) {
    if (jetexirSettingsFooter) jetexirSettingsFooter.classList.add('jetexir-submit-inactive');

    jetexirSettingsForm.addEventListener('change', function () {
      jetexirActiveSettingsForm();
    });

    jetexirSettingsForm.addEventListener('submit', function () {
      window.removeEventListener("beforeunload", jetexirSettingsFormChangeAlert);
    });

    if (jetexirSettingsResetButton) {
      jetexirSettingsResetButton.addEventListener("click", () => {
        jetexirSettingsSubmitActive = false;

        if (jetexirSettingsFooter) jetexirSettingsFooter.classList.add('jetexir-submit-inactive');

        window.removeEventListener("beforeunload", jetexirSettingsFormChangeAlert);
      });
    }
  }

  function jetexirInitGradient() {
    const wpGradientSelectColor = $('.jetexir-gradient-select-color input[type="text"]');
    if (wpGradientSelectColor.length) {
      wpGradientSelectColor.wpColorPicker({
        defaultColor: false, change: function (event, ui) {
          let gradientWrap = $(event.target).closest('.jetexir-gradient-color-picker-wrap'),
            gradientContainer = gradientWrap.find('.jetexir-gradient-color-picker'),
            selectedColor = ui.color.toString();

          gradientContainer.find('.jetexir-gradient-color-point.is-active').attr('data-color', selectedColor);
          gradientContainer.find('.jetexir-gradient-color-point.is-active span').css('background-color', selectedColor);
          jetexirUpdateGradient(gradientWrap);
          jetexirActiveSettingsForm();

        }, clear: function () {
          jetexirActiveSettingsForm();
        }, hide: true, palettes: jetexirWpColorPickerPalettes
      });
    }

    $('.jetexir-gradient-color-picker-wrap').not('.jetexir-gradient-color-picker-initialized').each(function () {
      let gradientWrap = $(this),
        gradientField = gradientWrap.find('input.jetexir-gradient-color-picker-value[type="hidden"]'),
        gradientContainer = gradientWrap.find('.jetexir-gradient-color-picker'),
        gradientSelectColor = gradientWrap.find('.jetexir-gradient-select-color'),
        gradientInfo = JSON.parse(gradientField.val().replaceAll("'", '"')), gradientPoint, gradientPointX, maxX,
        minX = 5, minY = 5, pX, gradientPoints = Object.entries(gradientInfo.colors);

      gradientWrap.addClass('jetexir-gradient-color-picker-initialized');
      gradientSelectColor.append('<a href="#" class="jetexir-gradient-remove-color" style="display: ' + (gradientPoints.length > 2 ? 'block' : 'none') + '">' + Jetexir.removeText + '</a>');

      for (let [index, [key, value]] of gradientPoints.entries()) {
        gradientPoint = gradientWrap.find('div[data-index="' + index + '"]');

        if (gradientPoint.length) {
          gradientPoint.css('left', ((gradientContainer.outerWidth() - gradientPoint.outerWidth() - (minX * 2)) / 100) * key + minX);

          maxX = gradientContainer.outerWidth() - gradientPoint.outerWidth() - minX;
          gradientPointX = document.getElementById(gradientWrap.attr('id') + '-' + index);
          minY = parseInt(gradientPoint.position().top + (gradientPoint.outerHeight() / 2));
          gradientPoint.css('top', minY);
          jetexirGradientPointDrag(gradientPointX, minX, maxX, minY);
        }
      }
    });

    $('.jetexir-gradient-remove-color').unbind('click').on('click', function (e) {
      e.preventDefault();
      let gradientWrap = $(this).closest('.jetexir-gradient-color-picker-wrap'),
        gradientPoints = gradientWrap.find('.jetexir-gradient-color-point');
      if (gradientPoints.length <= 2) return;

      gradientWrap.find('.jetexir-gradient-color-point.is-active').remove();
      gradientPoints = gradientWrap.find('.jetexir-gradient-color-point');
      if (gradientPoints.length <= 2) $(this).hide();

      gradientWrap.find('.jetexir-gradient-color-point').first().addClass('is-active').trigger('click');

      jetexirUpdateGradient(gradientWrap);
      jetexirActiveSettingsForm();
    });

    $('.jetexir-gradient-color-picker-wrap .jetexir-gradient-color-picker').unbind('click').on('click', function (e) {
      if (!$(e.target).is('.jetexir-gradient-color-picker')) return;

      var gradientContainer = $(this), gradientWrap = gradientContainer.closest('.jetexir-gradient-color-picker-wrap'),
        gradientMaxColors = parseInt(gradientWrap.data('max-colors')),
        gradientPoints = gradientWrap.find('.jetexir-gradient-color-point'), maxX, minX = 5, pX;

      if (gradientMaxColors <= gradientPoints.length) return;

      gradientPoints.removeClass('is-active');

      var gradientWrapID = gradientWrap.attr('id'), leftX = e.pageX - gradientContainer.offset().left,
        gradientPointFirst = gradientContainer.find('.jetexir-gradient-color-point').first(),
        gradientPoint = gradientPointFirst.clone(), gradientPointX, minY = gradientPointFirst.css('top'),
        gradientRemove = gradientWrap.find('.jetexir-gradient-remove-color'),
        randomColor = "#000000".replace(/0/g, function () {
          return (~~(Math.random() * 16)).toString(16);
        });
      maxX = gradientContainer.outerWidth() - gradientPointFirst.outerWidth() - minX;
      leftX -= parseInt(gradientPointFirst.outerWidth() / 2);
      leftX = leftX < minX ? minX : leftX;
      leftX = leftX > maxX ? maxX : leftX;
      pX = parseInt(Math.round(leftX / maxX * 100 * 100) / 100);
      gradientPoint.attr('data-color', randomColor).attr('data-position', pX);
      gradientPoint.css('left', leftX).addClass('is-active');
      gradientPoint.find('span').css('background-color', randomColor);

      gradientContainer.append(gradientPoint);
      gradientRemove.show();

      gradientWrap.find('.jetexir-gradient-color-point').each(function (index) {
        $(this).attr('id', gradientWrapID + '-' + index);
        $(this).attr('data-index', index);
      });

      gradientPointX = document.getElementById(gradientWrapID + '-' + gradientPoint.attr('data-index'));
      jetexirGradientPointDrag(gradientPointX, minX, maxX, minY);
      jetexirInitGradient();
      gradientPoint.trigger('click');
    });

    $('.jetexir-gradient-color-picker-wrap .jetexir-gradient-color-rotation .jetexir-input-range').unbind('input').on('input', function () {
      jetexirUpdateGradient($(this).closest('.jetexir-gradient-color-picker-wrap'));
    });

    $('.jetexir-gradient-color-picker-wrap .jetexir-gradient-color-shape input[type="radio"]').unbind('click').on('click', function () {
      jetexirUpdateGradient($(this).closest('.jetexir-gradient-color-picker-wrap'));
    });

    $('.jetexir-gradient-color-picker-wrap .jetexir-gradient-color-type input[type="radio"]').unbind('click').on('click', function () {
      let gradientWrap = $(this).closest('.jetexir-gradient-color-picker-wrap');

      gradientWrap.find('.jetexir-gradient-color-variant').hide();

      if ($(this).val() === 'linear-gradient') {
        gradientWrap.find('.jetexir-gradient-color-rotation').show();

      } else if ($(this).val() === 'radial-gradient') {
        gradientWrap.find('.jetexir-gradient-color-shape').show();
      }

      jetexirUpdateGradient(gradientWrap);
    });

    $('.jetexir-gradient-color-picker-wrap .jetexir-gradient-color-point').unbind('click').on('click', function () {
      let gradientWrap = $(this).closest('.jetexir-gradient-color-picker-wrap');
      gradientWrap.find('.jetexir-gradient-color-point').removeClass('is-active');
      $(this).addClass('is-active');
      gradientWrap.find('.jetexir-wp-color-picker input.wp-color-picker').val($(this).attr('data-color')).trigger('change');
    });
  }

  function jetexirGradientPointDrag(elm, minX, maxX, minY) {
    jetexirDrag.init(elm, null, minX, maxX, minY, minY);
    maxX -= minX;

    elm.onDrag = function (elm, x, y) {
      x -= minX;
      let pX = Math.round(x / maxX * 100 * 100) / 100;
      elm.setAttribute('data-position', pX);

      jetexirUpdateGradient($(elm.closest('.jetexir-gradient-color-picker-wrap')));
      jetexirActiveSettingsForm();
    }
  }

  function jetexirUpdateGradient(gradientWrap) {
    let gradientContainer = gradientWrap.find('.jetexir-gradient-color-picker'),
      gradientField = gradientWrap.find('input.jetexir-gradient-color-picker-value[type="hidden"]'),
      gradientPoints = gradientContainer.find('.jetexir-gradient-color-point'),
      gradientRotation = gradientWrap.find('.jetexir-input-range').val(),
      gradientType = gradientWrap.find('.jetexir-gradient-color-type input[type="radio"]:checked').val(),
      gradientShape = gradientWrap.find('.jetexir-gradient-color-shape input[type="radio"]:checked').val(),
      gradientColors = {}, gradientColorPoints = [], cssGradient, firstParam, gradientFieldValue = {};

    if (gradientPoints.length) {
      firstParam = gradientType === 'linear-gradient' ? gradientRotation + 'deg' : gradientShape;

      gradientPoints.each(function () {
        gradientColors[parseInt($(this).attr('data-position'))] = $(this).attr('data-color');
      });

      for (let gradientPos in gradientColors) {
        gradientColorPoints.push(gradientColors[gradientPos] + ' ' + gradientPos + '%');
      }

      cssGradient = gradientType + '(' + firstParam + ', ' + gradientColorPoints.join(', ') + ')';
      gradientContainer.css('background', cssGradient);
    }

    gradientFieldValue.function = gradientType;
    gradientFieldValue.rotate = gradientRotation;
    gradientFieldValue.shape = gradientShape;
    gradientFieldValue.colors = gradientColors;

    gradientField.val(JSON.stringify(gradientFieldValue));
  }

  jetexirInitGradient();

  function jetexirWpColorPickerInit() {
    let wpColorPicker = $('.jetexir-wp-color-picker,.jetexir-color-palette').not('.jetexir-gradient-select-color').find('input[type="text"]');

    if (wpColorPicker.length) {
      wpColorPicker.wpColorPicker(jetexirWpColorPickerOptions);

      setTimeout(function () {
        $('.jetexir-color-palette[data-removable="1"]').each(function () {
          let jetexirPickerContainer = $(this).find('.wp-picker-container');

          if (jetexirPickerContainer.length > 0) {
            jetexirPickerContainer.append('<button type="button" class="jetexir-remove-color"><i class="jetexir-icon-cross"></i></button>');
          }
        });

        jetexirColorPaletteInit();
      }, 500);
    }
  }

  jetexirWpColorPickerInit();

  $('.jetexir-color-palette .jetexir-add-color').unbind("click").on('click', function (e) {
    e.preventDefault();

    let $this = $(this), jetexirColorPalette = $this.closest('.jetexir-color-palette'),
      jetexirColorPaletteItems = jetexirColorPalette.find('.jetexir-color-palette-items'),
      jetexirColorPaletteMax = jetexirColorPalette.attr('data-max-items'),
      jetexirColorInput = jetexirColorPalette.find('.wp-picker-container').last().find('.wp-picker-input-wrap .wp-color-picker'),
      currentColorCount = jetexirColorPalette.find('.wp-picker-container').length;

    if (jetexirColorPaletteMax !== undefined && parseInt(jetexirColorPaletteMax) <= currentColorCount) return;

    let jetexirColorInputClone = jetexirColorInput.clone(),
      jetexirColorInputCloneName = jetexirColorInputClone.attr('name');
    let jetexirColorIndex = jetexirColorInputCloneName.substring(jetexirColorInputCloneName.indexOf("[") + 1, jetexirColorInputCloneName.lastIndexOf("]"));
    jetexirColorIndex = parseInt(jetexirColorIndex);
    jetexirColorInputClone.attr('name', jetexirColorInputCloneName.replace('[' + jetexirColorIndex + ']', '[' + jetexirColorIndex + 1 + ']'));
    jetexirColorInputClone.removeClass('wp-color-picker');
    jetexirColorInputClone.val("#000000".replace(/0/g, function () {
      return (~~(Math.random() * 16)).toString(16);
    }));
    jetexirColorPaletteItems.append(jetexirColorInputClone);

    jetexirColorInputClone.wpColorPicker(jetexirWpColorPickerOptions);

    if (jetexirColorPaletteMax !== undefined && parseInt(jetexirColorPaletteMax) <= (currentColorCount + 1)) {
      $this.attr('disable', 'true');
    }

    if (jetexirColorPalette.attr('data-removable') === '1') {
      jetexirColorPalette.find('.jetexir-remove-color').show();
      jetexirColorInputClone.closest('.wp-picker-container').append('<button type="button" class="jetexir-remove-color"><i class="jetexir-icon-cross"></i></button>');
    }

    jetexirColorPaletteInit();
    jetexirActiveSettingsForm();
  });

  $('.jetexir-add-repeatable').unbind("click").on('click', function (e) {
    e.preventDefault();

    let $this = $(this), repeatablePosition = $this.data('position'),
      jetexirRepeatable = $this.closest('.jetexir-repeatable'), repeatableMax = jetexirRepeatable.data('max-repeat'),
      jetexirRepeatableFieldsWrap = jetexirRepeatable.find('.jetexir-repeatable-fields-wrap'),
      jetexirRepeatableFirstFieldsWrap = jetexirRepeatable.find('.jetexir-repeatable-fields-wrap').first(),
      jetexirRepeatableLastFieldsWrap = jetexirRepeatable.find('.jetexir-repeatable-fields-wrap').last(),
      jetexirRepeatableCloneFieldsWrap;

    if (repeatableMax !== undefined && jetexirRepeatableFieldsWrap.length >= parseInt(repeatableMax)) {
      return;
    }

    if (repeatableMax !== undefined && jetexirRepeatableFieldsWrap.length + 1 >= parseInt(repeatableMax)) {
      jetexirRepeatable.find('.jetexir-add-repeatable').attr('disable', 'true');
    }

    if (jetexirRepeatableFieldsWrap.length >= 1) {
      jetexirRepeatable.find('.jetexir-remove-repeatable').show();
      jetexirRepeatable.find('.jetexir-move-up-repeatable').show();
      jetexirRepeatable.find('.jetexir-move-down-repeatable').show();
    }

    if (jetexirRepeatableFirstFieldsWrap.length) {
      repeatablePosition = repeatablePosition === undefined ? 'end' : repeatablePosition;
      jetexirRepeatableCloneFieldsWrap = jetexirRepeatableFirstFieldsWrap.clone();

      jetexirRepeatableCloneFieldsWrap.find('input,textarea').each(function () {
        let defaultValue = $(this).data('default');
        defaultValue = defaultValue === undefined ? '' : defaultValue;
        $(this).val(defaultValue);
      });

      if (repeatablePosition === 'start') {
        jetexirRepeatableCloneFieldsWrap.insertBefore(jetexirRepeatableFirstFieldsWrap);
      } else {
        jetexirRepeatableCloneFieldsWrap.insertAfter(jetexirRepeatableLastFieldsWrap);
      }
    }

    jetexirRepeatableInit();
    jetexirActiveSettingsForm();
  });

  function jetexirColorPaletteInit() {
    $('.jetexir-color-palette .jetexir-remove-color').unbind("click").on('click', function (e) {
      let $this = $(this), jetexirColorPalette = $this.closest('.jetexir-color-palette');

      $this.closest('.wp-picker-container').slideUp("normal", function () {
        $(this).remove();

        jetexirColorPalette.find('.jetexir-add-color').removeAttr('disable');

        if (jetexirColorPalette.find('.wp-picker-container').length <= 1) {
          jetexirColorPalette.find('.jetexir-remove-color').hide();
        }

        jetexirActiveSettingsForm();
      });
    });
  }

  function jetexirRepeatableInit() {
    $('.jetexir-remove-repeatable').unbind("click").on('click', function (e) {
      e.preventDefault();
      let $this = $(this), jetexirRepeatable = $this.closest('.jetexir-repeatable');

      //$this.closest('.jetexir-repeatable-fields-wrap').remove();
      $this.closest('.jetexir-repeatable-fields-wrap').slideUp("normal", function () {
        $(this).remove();

        jetexirRepeatable.find('.jetexir-add-repeatable').removeAttr('disable');

        if (jetexirRepeatable.find('.jetexir-repeatable-fields-wrap').length <= 1) {
          jetexirRepeatable.find('.jetexir-remove-repeatable').hide();
          jetexirRepeatable.find('.jetexir-move-up-repeatable').hide();
          jetexirRepeatable.find('.jetexir-move-down-repeatable').hide();
        }

        jetexirActiveSettingsForm();
      });
    });

    $('.jetexir-move-up-repeatable').unbind("click").on('click', function (e) {
      e.preventDefault();

      let $this = $(this), jetexirRepeatableFieldsWrap = $this.closest('.jetexir-repeatable-fields-wrap'),
        jetexirPrevRepeatableFieldsWrap = jetexirRepeatableFieldsWrap.prev('.jetexir-repeatable-fields-wrap');

      let copyTo = jetexirPrevRepeatableFieldsWrap.clone(true), copyFrom = jetexirRepeatableFieldsWrap.clone(true);
      jetexirPrevRepeatableFieldsWrap.replaceWith(copyFrom);
      jetexirRepeatableFieldsWrap.replaceWith(copyTo);

      jetexirActiveSettingsForm();
    });

    $('.jetexir-move-down-repeatable').unbind("click").on('click', function (e) {
      e.preventDefault();

      let $this = $(this), jetexirRepeatableFieldsWrap = $this.closest('.jetexir-repeatable-fields-wrap'),
        jetexirNextRepeatableFieldsWrap = jetexirRepeatableFieldsWrap.next('.jetexir-repeatable-fields-wrap');

      let copyTo = jetexirNextRepeatableFieldsWrap.clone(true), copyFrom = jetexirRepeatableFieldsWrap.clone(true);
      jetexirNextRepeatableFieldsWrap.replaceWith(copyFrom);
      jetexirRepeatableFieldsWrap.replaceWith(copyTo);

      jetexirActiveSettingsForm();
    });
  }

  setTimeout(function () {
    $('.jetexir-repeatable').each(function () {
      let jetexirRepeatableFieldsWrap = $(this).find('.jetexir-repeatable-fields-wrap');

      if (jetexirRepeatableFieldsWrap !== undefined && jetexirRepeatableFieldsWrap.length > 1) {
        $(this).find('.jetexir-remove-repeatable').show();
        $(this).find('.jetexir-move-up-repeatable').show();
        $(this).find('.jetexir-move-down-repeatable').show();
      }
    });

    jetexirRepeatableInit();
  }, 500);

  function jetexirSortableElement(elm, sortCallback = null) {
    elm.find('tbody').sortable({
      items: 'tr', cursor: 'move', axis: 'y', handle: '.sort', scrollSensitivity: 40, helper: function (e, ui) {
        ui.children().each(function () {
          $(this).width($(this).width());
        });
        ui.css('left', -10);
        return ui;
      }
    });

    elm.find('tbody').on("sortstart", function (event, ui) {
      ui.item.css('background-color', '#f9f3ff');
      if (typeof sortCallback == 'function') {
        sortCallback(event, ui);
      }
    });

    elm.find('tbody').on("sortstop", function (event, ui) {
      ui.item.removeAttr('style');
      elm.find('tbody tr').each(function (index, tr) {
        $('input.jetexir-dtu-row-order', tr).val(parseInt(index));
      });
      if (typeof sortCallback == 'function') {
        sortCallback(event, ui);
      }
    });
  }

  // Data Table UI
  function jetexirDataTableUiModal($this, jetexirDataTableID, modalTarget) {
    if (modalTarget !== undefined) {
      let modalTargetElm = $(modalTarget);
      if (modalTargetElm.length) {
        let displayActiveField = parseInt($this.data('display-active-field')),
          activeField = parseInt($this.data('active-field'));

        modalTargetElm.attr('data-dtu-id', jetexirDataTableID);
        modalTargetElm.find('#jetexir-toggle-dtu-row-active').prop('checked', activeField === 1);
        if (displayActiveField === 1) {
          modalTargetElm.find('.jetexir-modal-footer .jetexir-field-toggle').show();
        } else {
          modalTargetElm.find('.jetexir-modal-footer .jetexir-field-toggle').hide();
        }

        modalTargetElm.find('.jetexir-modal-message').html('');
        modalTargetElm.find('.jetexir-modal-footer').hide();
        modalTargetElm.find('.jetexir-modal-footer .jetexir-button-primary').html($this.data('primary-button-text'));
        modalTargetElm.find('.jetexir-modal-title').html($this.data('modal-title'));
        modalTargetElm.find('.jetexir-modal-body').html('<div class="jetexir-loader-wrap"><div class="jetexir-loader"></div></div>');
      }
    }
  }

  function jetexirDataTableUiInit() {
    // Data table action buttons
    $('.jetexir-data-table-ui .jetexir-dtu-action').on('click', function (e) {
      e.preventDefault();

      let $this = $(this), jetexirDataTable = $this.closest('.jetexir-data-table-ui'),
        jetexirDataTableID = jetexirDataTable.data('id'), jetexirDataTableRow = $this.closest('tr'),
        jetexirDataTableRowId = jetexirDataTableRow.data('id'), jetexirDataTableAction = $this.data('action'),
        jetexirDataTableActionType = $this.data('action-type'),
        jetexirDataTableBody = jetexirDataTable.find('.jetexir-dtu-body'),
        jetexirDataTableTable = jetexirDataTable.find('.jetexir-dtu-table'),
        jetexirDataTableRowCount = jetexirDataTable.find('.jetexir-dtu-row-count'),
        modalTarget = $this.data('jetexir-target'), modalTargetElm = $(modalTarget);

      if (jetexirAjax || jetexirDataTableActionType === 'delete' && !confirm(Jetexir.dtuConfirmDelete)) {
        return;
      }

      jetexirAjax = true;

      jetexirDataTableUiModal($this, jetexirDataTableID, modalTarget);

      $.post(Jetexir.ajaxUrl, {
        nonce: Jetexir.ajaxNonce,
        action: 'jetexir_data_table_ui_action',
        data_table_id: jetexirDataTableID,
        row_id: jetexirDataTableRowId,
        row_action: jetexirDataTableAction
      })
        .done(function (data) {
          if (jetexirDataTableActionType === 'delete') {
            jetexirDataTableRow.fadeTo(200, 0.01, () => {
              jetexirDataTableRow.children('td, th')
                .animate({padding: 0})
                .wrapInner('<div />')
                .children()
                .slideUp(200, () => {
                  jetexirDataTableRow.remove();

                  if (data?.data?.table && data?.data.table !== '') {
                    jetexirDataTableTable.replaceWith(data?.data.table);

                    if (jetexirDataTableRowCount.length && data?.data?.row_count) jetexirDataTableRowCount.html(data?.data?.row_count)

                    jetexirModalInit(jetexirDataTableBody);
                    jetexirDataTableUiInit();
                  }
                });
            });

          } else if (jetexirDataTableActionType === 'edit') {
            modalTargetElm.find('.jetexir-modal-footer').show();
            modalTargetElm.find('.jetexir-modal-body').html(data.data.content);

            setTimeout(function () {
              jetexirWpColorPickerInit();
              jetexirInitGradient();
            }, 500);
          }

          jetexirCopyTextInit();

          if (data.data?.redirect && data.data.redirect !== '') window.location.href = data.data.redirect;

          if (data.data?.refresh) window.location.reload(true);
        })
        .fail(function (xhr, status, error) {
          if (xhr.responseJSON?.data?.refresh) setTimeout(function () {
            window.location.reload(true);
          }, 3000);
        })
        .always(function () {
          jetexirAjax = false;
        });
    });

    $('.jetexir-data-table-sortable').each(function () {
      jetexirSortableElement($(this).find('.jetexir-dtu-table'), function (event, ui) {
        if (event.type === 'sortstop') {
          $(event.target).closest('.jetexir-data-table-ui').find('button.jetexir-dtu-save-changes').prop('disabled', false);
        }
      });
    });
  }

  // Data Table UI save rows changes
  $('.jetexir-data-table-ui button.jetexir-dtu-save-changes').on('click', function () {
    let $this = $(this), jetexirDataTable = $this.closest('.jetexir-data-table-ui'),
      jetexirDataTableID = jetexirDataTable.data('id'),
      jetexirDataTableBody = jetexirDataTable.find('.jetexir-dtu-body'),
      jetexirDataTableRowCount = jetexirDataTable.find('.jetexir-dtu-row-count'),
      jetexirDataTableTable = jetexirDataTable.find('.jetexir-dtu-table'), jetexirDataTableRowOrders = {};

    if (jetexirAjax) {
      return;
    }
    jetexirAjax = true;

    jetexirDataTableTable.find('.jetexir-dtu-row-order').each(function () {
      jetexirDataTableRowOrders[$(this).closest('tr').data('id')] = parseInt($(this).val());
    });

    if (jetexirDataTableRowOrders.length === 0) return;

    $.post(Jetexir.ajaxUrl, {
      nonce: Jetexir.ajaxNonce,
      action: 'jetexir_data_table_ui_action',
      data_table_id: jetexirDataTableID,
      row_id: -1,
      row_action: 'save_changes',
      row_orders: jetexirDataTableRowOrders
    })
      .done(function (data) {
        if (data?.data?.table && data?.data.table !== '') {
          jetexirDataTableTable.replaceWith(data?.data.table);

          if (jetexirDataTableRowCount.length && data?.data?.row_count) jetexirDataTableRowCount.html(data?.data?.row_count)

          jetexirModalInit(jetexirDataTableBody);
          jetexirDataTableUiInit();
        }

        jetexirDataTable.find('button.jetexir-dtu-save-changes').prop('disabled', true);

        if (data.data?.redirect && data.data.redirect !== '') window.location.href = data.data.redirect;
      })
      .fail(function (xhr, status, error) {
        if (xhr.responseJSON?.data?.refresh) setTimeout(function () {
          window.location.reload(true);
        }, 3000);
      })
      .always(function () {
        jetexirAjax = false;
      });
  });

  $('.jetexir-dtu-bulk-actions button').on('click', function () {
    let $this = $(this), jetexirDataTableBulkActions = $this.closest('.jetexir-dtu-bulk-actions'),
      jetexirDataTableBulkAction = jetexirDataTableBulkActions.find('select').val(), jetexirDataTableActionType,
      jetexirDataTableRowsSelected = [], jetexirDataTable = $this.closest('.jetexir-data-table-ui'),
      jetexirDataTableID = jetexirDataTable.data('id'),
      jetexirDataTableBody = jetexirDataTable.find('.jetexir-dtu-body'),
      jetexirDataTableRowCount = jetexirDataTable.find('.jetexir-dtu-row-count'),
      jetexirDataTableTable = jetexirDataTable.find('.jetexir-dtu-table');

    if (jetexirDataTableBulkAction.length === 0) return;

    jetexirDataTableActionType = jetexirDataTableBulkActions.find('select option[value="' + jetexirDataTableBulkAction + '"]').data('action-type');
    if (jetexirAjax || jetexirDataTableActionType === 'delete' && !confirm(Jetexir.dtuConfirmDelete)) {
      return;
    }

    jetexirDataTableTable.find('.jetexir-dtu-row-select:checked').each(function () {
      jetexirDataTableRowsSelected.push(parseInt($(this).val()));
    });

    if (jetexirDataTableRowsSelected.length === 0) return;

    jetexirAjax = true;

    $.post(Jetexir.ajaxUrl, {
      nonce: Jetexir.ajaxNonce,
      action: 'jetexir_data_table_ui_action',
      data_table_id: jetexirDataTableID,
      row_id: -1,
      row_action: 'bulk_action',
      bulk_action: jetexirDataTableBulkAction,
      row_ids: jetexirDataTableRowsSelected
    })
      .done(function (data) {
        if (data?.data?.table && data?.data.table !== '') {
          jetexirDataTableTable.replaceWith(data?.data.table);

          if (jetexirDataTableRowCount.length && data?.data?.row_count) jetexirDataTableRowCount.html(data?.data?.row_count)

          jetexirModalInit(jetexirDataTableBody);
          jetexirDataTableUiInit();
        }

        if (data.data?.redirect && data.data.redirect !== '') window.location.href = data.data.redirect;
      })
      .fail(function (xhr, status, error) {
        if (xhr.responseJSON?.data?.refresh) setTimeout(function () {
          window.location.reload(true);
        }, 3000);
      })
      .always(function () {
        jetexirAjax = false;
      });
  });

  // Add new button click event
  $('.jetexir-data-table-ui .jetexir-dtu-add-new').on('click', function () {
    if (jetexirAjax) return;
    jetexirAjax = true;

    let $this = $(this), jetexirDataTable = $this.closest('.jetexir-data-table-ui'),
      jetexirDataTableID = jetexirDataTable.data('id'), modalTarget = $this.data('jetexir-target'),
      modalTargetElm = $(modalTarget);

    jetexirDataTableUiModal($this, jetexirDataTableID, modalTarget);

    $.post(Jetexir.ajaxUrl, {
      nonce: Jetexir.ajaxNonce,
      action: 'jetexir_data_table_ui_action',
      data_table_id: jetexirDataTableID,
      row_id: -1,
      row_action: 'add_form'
    })
      .done(function (data) {
        modalTargetElm.find('.jetexir-modal-footer').show();
        modalTargetElm.find('.jetexir-modal-body').html(data.data.content);

        setTimeout(function () {
          jetexirWpColorPickerInit();
          jetexirInitGradient();
        }, 500);

        if (data.data?.redirect && data.data.redirect !== '') window.location.href = data.data.redirect;
      })
      .fail(function (xhr, status, error) {
        if (xhr.responseJSON?.data?.refresh) setTimeout(function () {
          window.location.reload(true);
        }, 3000);
      })
      .always(function () {
        jetexirAjax = false;
      });
  });

  // DataTableUI modal submit
  $('.jetexir-data-table-ui-modal .jetexir-modal-footer button.jetexir-button-primary').unbind('click').on('click', function () {
    let $this = $(this), jetexirModal = $this.closest('.jetexir-modal'),
      jetexirDataTableID = jetexirModal.data('dtu-id'),
      jetexirDataTable = $('.jetexir-data-table-ui[data-id="' + jetexirDataTableID + '"]'),
      rowActive = jetexirModal.find('input[name="jetexir_dtu-row-active"]').is(':checked'),
      rowId = jetexirModal.find('input[name="jetexir_row_id"]').val(),
      jetexirModalBody = jetexirModal.find('.jetexir-modal-body'),
      jetexirModalMessage = jetexirModal.find('.jetexir-modal-message'),
      jetexirCloseButton = jetexirModal.find('.jetexir-button-close'),
      jetexirDataTableBody = jetexirDataTable.find('.jetexir-dtu-body'),
      jetexirDataTableTable = jetexirDataTable.find('.jetexir-dtu-table'),
      jetexirDataTableRowCount = jetexirDataTable.find('.jetexir-dtu-row-count');

    $.post(Jetexir.ajaxUrl, {
      nonce: Jetexir.ajaxNonce,
      action: 'jetexir_data_table_ui_action',
      data_table_id: jetexirDataTableID,
      row_id: parseInt(rowId),
      row_action: 'save_form',
      row_active: rowActive ? 1 : 0,
      form_data: jetexirModalBody.serialize()
    })
      .done(function (data) {
        if (data?.data?.message && data?.data.message !== '') {
          jetexirModalMessage.html(data?.data.message);
        }

        if (data.data?.redirect && data.data.redirect !== '') window.location.href = data.data.redirect;

        if (data?.data?.table && data?.data.table !== '') {
          jetexirDataTableTable.replaceWith(data?.data.table);

          if (jetexirDataTableRowCount.length && data?.data?.row_count) jetexirDataTableRowCount.html(data?.data?.row_count)

          jetexirModalInit(jetexirDataTableBody);
          jetexirDataTableUiInit();
        }

        setTimeout(function () {
          jetexirCloseButton.trigger('click');

          if (data.data?.refresh) window.location.reload(true);
        }, 2000);
      })
      .fail(function (xhr, status, error) {
        if (xhr.responseJSON?.data?.message && xhr.responseJSON?.data.message !== '') {
          jetexirModalMessage.html(xhr.responseJSON?.data.message);
        }

        if (xhr.responseJSON?.data?.refresh) setTimeout(function () {
          window.location.reload(true);
        }, 3000);
      })
      .always(function () {
        jetexirModal.animate({scrollTop: 0}, "slow");
        jetexirAjax = false;
      });
  });

  jetexirDataTableUiInit();


  /** Media methods */
  function jetexirMediaInit() {
    $('.jetexir-media-image').unbind('click').on('click', function () {
      let $this = $(this), mediaSelectID = $this.attr('data-id'), mediaWrap = $this.closest('.jetexir-media-wrap'),
        mediaInput = mediaWrap.find('input'), mediaImageIDs = mediaInput.val().split(',');

      const index = mediaImageIDs.indexOf(mediaSelectID);
      if (index > -1) {
        mediaImageIDs.splice(index, 1);
      }

      if (mediaImageIDs.length === 0) {
        mediaWrap.removeClass('jetexir-media-selected');
      }

      mediaInput.val(mediaImageIDs.join(','));
      $this.remove();
      jetexirActiveSettingsForm();
    });
  }

  $('.jetexir-media-select').on('click', function () {
    let $this = $(this), mediaWrap = $this.closest('.jetexir-media-wrap'), mediaWrapperID = mediaWrap.attr('id'),
      mediaTitle = mediaWrap.data('title'), mediaButton = mediaWrap.data('button'), mediaType = mediaWrap.data('type'),
      acceptExtensions = mediaWrap.data('accept-extensions'),
      multiSelection = parseInt(mediaWrap.data('multi-selection')) === 1,
      mediaMaxNumber = parseInt(mediaWrap.data('max-number')), mediaMultiple = mediaMaxNumber > 1,
      mediaImageContainer = mediaWrap.find('.jetexir-media-images'), mediaInput = mediaWrap.find('input'),
      mediaSelected = 1;

    /*if (jetexirWpMediaFrames.hasOwnProperty(mediaWrapperID)) {
        jetexirWpMediaFrames[mediaWrapperID].open();
        return;
    }*/

    // Create a new media frame
    jetexirWpMediaFrames[mediaWrapperID] = wp.media({
      title: mediaTitle, button: {
        text: mediaButton
      }, library: {
        type: mediaType
      }, multiple: mediaMultiple
    });

    jetexirWpMediaFrames[mediaWrapperID].once('uploader:ready', function () {
      var uploader = jetexirWpMediaFrames[mediaWrapperID].uploader.uploader.uploader; // Upload manager

      //Updating allowed extensions
      uploader.setOption('filters', {
        mime_types: [{extensions: acceptExtensions}]
      });

      //Trick to reinit field
      uploader.setOption('multi_selection', multiSelection);
    });

    jetexirWpMediaFrames[mediaWrapperID].on('open', function () {
      let selection = jetexirWpMediaFrames[mediaWrapperID].state().get('selection'),
        mediaIDs = mediaInput.val().split(',');

      if (mediaIDs.length > 0) {
        mediaIDs.forEach(function (id) {
          let attachment = wp.media.attachment(id);
          attachment.fetch();
          selection.add(attachment ? [attachment] : []);
        });
      }
    });

    // When an image is selected in the media frame...
    jetexirWpMediaFrames[mediaWrapperID].on('select', function () {
      mediaImageContainer.html('');

      // Get media attachment details from the frame state
      let attachments = jetexirWpMediaFrames[mediaWrapperID].state().get('selection'),
        attachmentIDs = attachments.map(function (attachment) {
          if (mediaSelected <= mediaMaxNumber) {
            attachment = attachment.toJSON();
            let attachmentUrl = attachment.url;
            if (attachment.type !== 'image') {
              if (attachment.hasOwnProperty('image') && attachment.image.hasOwnProperty('src') && attachment.image.src) {
                attachmentUrl = attachment.image.src;
              } else {
                attachmentUrl = attachment.icon;
              }
            }
            let imageTitle = attachment.id + ': ' + (attachment.caption.length > 0 ? attachment.caption : attachment.title) + ' (' + attachment.type + ')';
            mediaImageContainer.append('<div class="jetexir-media-image" data-id="' + attachment.id + '"><img src="' + attachmentUrl + '" title="' + imageTitle + '"/><span class="jetexir-media-image-title">' + imageTitle + '</span></div>');
          }
          mediaSelected++;
          return attachment.id;
        });

      attachmentIDs = attachmentIDs.slice(0, mediaMaxNumber);

      mediaWrap.addClass('jetexir-media-selected');

      // Send the attachment id to our hidden input
      mediaInput.val(attachmentIDs.join(','));

      jetexirMediaInit();
      jetexirActiveSettingsForm();
    });

    // Finally, open the modal on click
    jetexirWpMediaFrames[mediaWrapperID].open();
  });

  $('.jetexir-media-remove-all').on('click', function () {
    let $this = $(this), mediaWrap = $this.closest('.jetexir-media-wrap'), mediaInput = mediaWrap.find('input');

    mediaWrap.removeClass('jetexir-media-selected');
    mediaInput.val('');
    jetexirActiveSettingsForm();
  });

  jetexirMediaInit();


  /**
   * Copy text
   * */
  function jetexirCopyTextInit() {
    let jetexirCopyText = $('.jetexir-copy-text');
    if (navigator.clipboard) {
      jetexirCopyText.each(function () {
        if ($(this).attr('title') === undefined) $(this).attr('title', Jetexir.copyText);
      })
      jetexirCopyText.unbind('click').on('click', function () {
        let jetexirCopyTextElm = $(this),
          jetexirTextForCopy = jetexirCopyTextElm.attr('data-text') !== undefined ? jetexirCopyTextElm.attr('data-text') : jetexirCopyTextElm.text();
        navigator.clipboard.writeText(jetexirTextForCopy);
        jetexirCopyTextElm.addClass('jetexir-text-copied');

        setTimeout(function () {
          jetexirCopyTextElm.removeClass('jetexir-text-copied');
        }, 500);
      });
    } else {
      jetexirCopyText.removeClass('jetexir-copy-text');
    }
  }

  jetexirCopyTextInit();
});

/******/ })()
;