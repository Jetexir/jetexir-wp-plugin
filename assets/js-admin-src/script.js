jQuery(document).ready(function ($) {
  let waWpColorPickerPalettes = ['#333', '#5de0f0', '#608bf7', '#7fff3f', '#00b700', '#fff200', '#ffae63', '#e64f6f', '#ef32e3', '#d1c1ff', '#873eff'],
    waWpColorPickerOptions = {
      defaultColor: false, change: function (event, ui) {
        waActiveSettingsForm();
      }, clear: function () {
        waActiveSettingsForm();
      }, hide: true, palettes: waWpColorPickerPalettes
    },
    waSettingsSubmitActive = false,
    waWpMediaFrames = {};

  const waBody = $('body'),
    waContentWrap = $('#jetexir-content-wrap'),
    waSettingsHeader = $('#jetexir-settings-header'),
    waSettingsSidebar = $('#jetexir-sidebar'),
    waSettingsDisplaySidebar = $('#jetexir-display-sidebar'),
    waSettingsHideSidebar = $('#jetexir-hide-sidebar'),
    waSettingsSectionLinks = $('.jetexir-section-links ul'),
    waSettingsForm = document.getElementById('jetexir-settings-form'),
    waSettingsFooter = document.getElementById('jetexir-settings-footer'),
    waSettingsResetButton = document.getElementById("jetexir-settings-reset-button");

  let waContentWrapPrevScrollPos = waContentWrap.scrollTop(),
    waContentWrapCurrentScrollPos = waContentWrapPrevScrollPos,
    waPageRefreshedAfter = parseInt(Jetexir.pageRefreshedAfter);

  /**
   * Page refresh
   * */
  if (waPageRefreshedAfter > 0) {
    setTimeout(function () {
      if (Jetexir.pageRefreshUrl !== null)
        window.location.href = Jetexir.pageRefreshUrl;
      else
        window.location.reload(true);
    }, waPageRefreshedAfter);
  }

  /**
   * Hide header on scroll down and sticky on scroll to top
   * */
  waContentWrap.scroll(function () {
    waContentWrapCurrentScrollPos = $(this).scrollTop();
    if (waContentWrapPrevScrollPos < waContentWrapCurrentScrollPos && waContentWrapCurrentScrollPos > waSettingsHeader.outerHeight())
      waSettingsHeader.addClass('hide-header');
    else
      waSettingsHeader.removeClass('hide-header');

    waContentWrapPrevScrollPos = waContentWrapCurrentScrollPos;
  });

  /**
   * Sidebar menu
   * */
  waSettingsDisplaySidebar.on('click', function (e) {
    e.preventDefault();
    waSettingsSidebar.addClass('jetexir-mobile-sidebar');
    waBody.addClass('jetexir-mobile-sidebar-active');
  });
  waSettingsHideSidebar.on('click', function (e) {
    e.preventDefault();
    waSettingsSidebar.removeClass('jetexir-mobile-sidebar');
    waBody.removeClass('jetexir-mobile-sidebar-active');
  });

  /**
   * Auto scroll to active section link
   * */
  if (waSettingsSectionLinks.length) {
    let waSectionActiveLink = waSettingsSectionLinks.find('.jetexir-section-link-current'),
      waSectionOutsideActiveLink = waSettingsSectionLinks.outerWidth() - 100 < waSectionActiveLink.position().left,
      waSectionScrollActiveLink = waSectionActiveLink.position().left - waSectionActiveLink.outerWidth(true) - (waSettingsSectionLinks.outerWidth() / 3);

    if (isRtl) {
      waSectionOutsideActiveLink = waSectionActiveLink.position().left - 100 < 0;
    }

    if (waSectionOutsideActiveLink) {
      waSettingsSectionLinks.animate({
        scrollLeft: waSectionScrollActiveLink
      }, 500);
    }
  }

  /**
   * Modal methods
   * */
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

  function waModalInit(wrapper) {
    wrapper.find('[data-jetexir-toggle="modal"]').unbind('click').on('click', function () {
      let $this = $(this),
        modalTarget = $this.data('jetexir-target');

      if (modalTarget !== undefined) {
        let modalTargetElm = $(modalTarget);
        if (modalTargetElm.length) {
          waToggleModal(true, modalTarget);
        }
      }
    });
  }

  waModalInit(waBody);
  $('#jetexir-modal-overlay, [data-jetexir-dismiss="modal"]').on('click', function () {
    waToggleModal(false);
  });


  function waActiveSettingsForm() {
    if (waSettingsSubmitActive) return;
    waSettingsSubmitActive = true;

    if (waSettingsFooter) waSettingsFooter.classList.remove('jetexir-submit-inactive');
  }

  if (waSettingsForm) {
    if (waSettingsFooter) waSettingsFooter.classList.add('jetexir-submit-inactive');

    waSettingsForm.addEventListener('change', function () {
      waActiveSettingsForm();
    });

    if (waSettingsResetButton) {
      waSettingsResetButton.addEventListener("click", () => {
        waSettingsSubmitActive = false;

        if (waSettingsFooter) waSettingsFooter.classList.add('jetexir-submit-inactive');
      });
    }
  }

  function waInitGradient() {
    const wpGradientSelectColor = $('.jetexir-gradient-select-color input[type="text"]');
    if (wpGradientSelectColor.length) {
      wpGradientSelectColor.wpColorPicker({
        defaultColor: false, change: function (event, ui) {
          let gradientWrap = $(event.target).closest('.jetexir-gradient-color-picker-wrap'),
            gradientContainer = gradientWrap.find('.jetexir-gradient-color-picker'),
            selectedColor = ui.color.toString();

          gradientContainer.find('.jetexir-gradient-color-point.is-active').attr('data-color', selectedColor);
          gradientContainer.find('.jetexir-gradient-color-point.is-active span').css('background-color', selectedColor);
          waUpdateGradient(gradientWrap);
          waActiveSettingsForm();

        }, clear: function () {
          waActiveSettingsForm();
        }, hide: true, palettes: waWpColorPickerPalettes
      });
    }

    $('.jetexir-gradient-color-picker-wrap').not('.jetexir-gradient-color-picker-initialized').each(function () {
      let gradientWrap = $(this),
        gradientField = gradientWrap.find('input.jetexir-gradient-color-picker-value[type="hidden"]'),
        gradientContainer = gradientWrap.find('.jetexir-gradient-color-picker'),
        gradientSelectColor = gradientWrap.find('.jetexir-gradient-select-color'),
        gradientInfo = JSON.parse(gradientField.val().replaceAll("'", '"')),
        gradientPoint, gradientPointX, maxX, minX = 5, minY = 5, pX,
        gradientPoints = Object.entries(gradientInfo.colors);

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
          waGradientPointDrag(gradientPointX, minX, maxX, minY);
        }
      }
    });

    $('.jetexir-gradient-remove-color').unbind('click').on('click', function (e) {
      e.preventDefault();
      let gradientWrap = $(this).closest('.jetexir-gradient-color-picker-wrap'),
        gradientPoints = gradientWrap.find('.jetexir-gradient-color-point');
      if (gradientPoints.length <= 2)
        return;

      gradientWrap.find('.jetexir-gradient-color-point.is-active').remove();
      gradientPoints = gradientWrap.find('.jetexir-gradient-color-point');
      if (gradientPoints.length <= 2)
        $(this).hide();

      gradientWrap.find('.jetexir-gradient-color-point').first().addClass('is-active').trigger('click');

      waUpdateGradient(gradientWrap);
      waActiveSettingsForm();
    });

    $('.jetexir-gradient-color-picker-wrap .jetexir-gradient-color-picker').unbind('click').on('click', function (e) {
      if (!$(e.target).is('.jetexir-gradient-color-picker'))
        return;

      var gradientContainer = $(this),
        gradientWrap = gradientContainer.closest('.jetexir-gradient-color-picker-wrap'),
        gradientMaxColors = parseInt(gradientWrap.data('max-colors')),
        gradientPoints = gradientWrap.find('.jetexir-gradient-color-point'),
        maxX, minX = 5, pX;

      if (gradientMaxColors <= gradientPoints.length)
        return;

      gradientPoints.removeClass('is-active');

      var gradientWrapID = gradientWrap.attr('id'),
        leftX = e.pageX - gradientContainer.offset().left,
        gradientPointFirst = gradientContainer.find('.jetexir-gradient-color-point').first(),
        gradientPoint = gradientPointFirst.clone(),
        gradientPointX, minY = gradientPointFirst.css('top'),
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
      waGradientPointDrag(gradientPointX, minX, maxX, minY);
      waInitGradient();
      gradientPoint.trigger('click');
    });

    $('.jetexir-gradient-color-picker-wrap .jetexir-gradient-color-rotation .jetexir-input-range').unbind('input').on('input', function () {
      waUpdateGradient($(this).closest('.jetexir-gradient-color-picker-wrap'));
    });

    $('.jetexir-gradient-color-picker-wrap .jetexir-gradient-color-shape input[type="radio"]').unbind('click').on('click', function () {
      waUpdateGradient($(this).closest('.jetexir-gradient-color-picker-wrap'));
    });

    $('.jetexir-gradient-color-picker-wrap .jetexir-gradient-color-type input[type="radio"]').unbind('click').on('click', function () {
      let gradientWrap = $(this).closest('.jetexir-gradient-color-picker-wrap');

      gradientWrap.find('.jetexir-gradient-color-variant').hide();

      if ($(this).val() === 'linear-gradient') {
        gradientWrap.find('.jetexir-gradient-color-rotation').show();

      } else if ($(this).val() === 'radial-gradient') {
        gradientWrap.find('.jetexir-gradient-color-shape').show();
      }

      waUpdateGradient(gradientWrap);
    });

    $('.jetexir-gradient-color-picker-wrap .jetexir-gradient-color-point').unbind('click').on('click', function () {
      let gradientWrap = $(this).closest('.jetexir-gradient-color-picker-wrap');
      gradientWrap.find('.jetexir-gradient-color-point').removeClass('is-active');
      $(this).addClass('is-active');
      gradientWrap.find('.jetexir-wp-color-picker input.wp-color-picker').val($(this).attr('data-color')).trigger('change');
    });
  }

  function waGradientPointDrag(elm, minX, maxX, minY) {
    waDrag.init(elm, null, minX, maxX, minY, minY);
    maxX -= minX;

    elm.onDrag = function (elm, x, y) {
      x -= minX;
      let pX = Math.round(x / maxX * 100 * 100) / 100;
      elm.setAttribute('data-position', pX);

      waUpdateGradient($(elm.closest('.jetexir-gradient-color-picker-wrap')));
      waActiveSettingsForm();
    }
  }

  function waUpdateGradient(gradientWrap) {
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

  waInitGradient();

  function waWpColorPickerInit() {
    let wpColorPicker = $('.jetexir-wp-color-picker,.jetexir-color-palette').not('.jetexir-gradient-select-color').find('input[type="text"]');

    if (wpColorPicker.length) {
      wpColorPicker.wpColorPicker(waWpColorPickerOptions);

      setTimeout(function () {
        $('.jetexir-color-palette[data-removable="1"]').each(function () {
          let waPickerContainer = $(this).find('.wp-picker-container');

          if (waPickerContainer.length > 0) {
            waPickerContainer.append('<button type="button" class="jetexir-remove-color"><i class="jetexir-icon-cross"></i></button>');
          }
        });

        waColorPaletteInit();
      }, 500);
    }
  }

  waWpColorPickerInit();

  $('.jetexir-color-palette .jetexir-add-color').unbind("click").on('click', function (e) {
    e.preventDefault();

    let $this = $(this),
      waColorPalette = $this.closest('.jetexir-color-palette'),
      waColorPaletteItems = waColorPalette.find('.jetexir-color-palette-items'),
      waColorPaletteMax = waColorPalette.attr('data-max-items'),
      waColorInput = waColorPalette.find('.wp-picker-container').last().find('.wp-picker-input-wrap .wp-color-picker'),
      currentColorCount = waColorPalette.find('.wp-picker-container').length;

    if (waColorPaletteMax !== undefined && parseInt(waColorPaletteMax) <= currentColorCount)
      return;

    let waColorInputClone = waColorInput.clone(),
      waColorInputCloneName = waColorInputClone.attr('name');
    let waColorIndex = waColorInputCloneName.substring(
      waColorInputCloneName.indexOf("[") + 1,
      waColorInputCloneName.lastIndexOf("]")
    );
    waColorIndex = parseInt(waColorIndex);
    waColorInputClone.attr('name', waColorInputCloneName.replace('[' + waColorIndex + ']', '[' + waColorIndex + 1 + ']'));
    waColorInputClone.removeClass('wp-color-picker');
    waColorInputClone.val("#000000".replace(/0/g, function () {
      return (~~(Math.random() * 16)).toString(16);
    }));
    waColorPaletteItems.append(waColorInputClone);

    waColorInputClone.wpColorPicker(waWpColorPickerOptions);

    if (waColorPaletteMax !== undefined && parseInt(waColorPaletteMax) <= (currentColorCount + 1)) {
      $this.attr('disable', 'true');
    }

    if (waColorPalette.attr('data-removable') === '1') {
      waColorPalette.find('.jetexir-remove-color').show();
      waColorInputClone.closest('.wp-picker-container').append('<button type="button" class="jetexir-remove-color"><i class="jetexir-icon-cross"></i></button>');
    }

    waColorPaletteInit();
    waActiveSettingsForm();
  });

  $('.jetexir-add-repeatable').unbind("click").on('click', function (e) {
    e.preventDefault();

    let $this = $(this), repeatablePosition = $this.data('position'),
      waRepeatable = $this.closest('.jetexir-repeatable'), repeatableMax = waRepeatable.data('max-repeat'),
      waRepeatableFieldsWrap = waRepeatable.find('.jetexir-repeatable-fields-wrap'),
      waRepeatableFirstFieldsWrap = waRepeatable.find('.jetexir-repeatable-fields-wrap').first(),
      waRepeatableLastFieldsWrap = waRepeatable.find('.jetexir-repeatable-fields-wrap').last(),
      waRepeatableCloneFieldsWrap;

    if (repeatableMax !== undefined && waRepeatableFieldsWrap.length >= parseInt(repeatableMax)) {
      return;
    }

    if (repeatableMax !== undefined && waRepeatableFieldsWrap.length + 1 >= parseInt(repeatableMax)) {
      waRepeatable.find('.jetexir-add-repeatable').attr('disable', 'true');
    }

    if (waRepeatableFieldsWrap.length >= 1) {
      waRepeatable.find('.jetexir-remove-repeatable').show();
      waRepeatable.find('.jetexir-move-up-repeatable').show();
      waRepeatable.find('.jetexir-move-down-repeatable').show();
    }

    if (waRepeatableFirstFieldsWrap.length) {
      repeatablePosition = repeatablePosition === undefined ? 'end' : repeatablePosition;
      waRepeatableCloneFieldsWrap = waRepeatableFirstFieldsWrap.clone();

      waRepeatableCloneFieldsWrap.find('input,textarea').each(function () {
        let defaultValue = $(this).data('default');
        defaultValue = defaultValue === undefined ? '' : defaultValue;
        $(this).val(defaultValue);
      });

      if (repeatablePosition === 'start') {
        waRepeatableCloneFieldsWrap.insertBefore(waRepeatableFirstFieldsWrap);
      } else {
        waRepeatableCloneFieldsWrap.insertAfter(waRepeatableLastFieldsWrap);
      }
    }

    waRepeatableInit();
    waActiveSettingsForm();
  });

  function waColorPaletteInit() {
    $('.jetexir-color-palette .jetexir-remove-color').unbind("click").on('click', function (e) {
      let $this = $(this),
        waColorPalette = $this.closest('.jetexir-color-palette');

      $this.closest('.wp-picker-container').slideUp("normal", function () {
        $(this).remove();

        waColorPalette.find('.jetexir-add-color').removeAttr('disable');

        if (waColorPalette.find('.wp-picker-container').length <= 1) {
          waColorPalette.find('.jetexir-remove-color').hide();
        }

        waActiveSettingsForm();
      });
    });
  }

  function waRepeatableInit() {
    $('.jetexir-remove-repeatable').unbind("click").on('click', function (e) {
      e.preventDefault();
      let $this = $(this), waRepeatable = $this.closest('.jetexir-repeatable');

      //$this.closest('.jetexir-repeatable-fields-wrap').remove();
      $this.closest('.jetexir-repeatable-fields-wrap').slideUp("normal", function () {
        $(this).remove();

        waRepeatable.find('.jetexir-add-repeatable').removeAttr('disable');

        if (waRepeatable.find('.jetexir-repeatable-fields-wrap').length <= 1) {
          waRepeatable.find('.jetexir-remove-repeatable').hide();
          waRepeatable.find('.jetexir-move-up-repeatable').hide();
          waRepeatable.find('.jetexir-move-down-repeatable').hide();
        }

        waActiveSettingsForm();
      });
    });

    $('.jetexir-move-up-repeatable').unbind("click").on('click', function (e) {
      e.preventDefault();

      let $this = $(this), waRepeatableFieldsWrap = $this.closest('.jetexir-repeatable-fields-wrap'),
        waPrevRepeatableFieldsWrap = waRepeatableFieldsWrap.prev('.jetexir-repeatable-fields-wrap');

      let copyTo = waPrevRepeatableFieldsWrap.clone(true), copyFrom = waRepeatableFieldsWrap.clone(true);
      waPrevRepeatableFieldsWrap.replaceWith(copyFrom);
      waRepeatableFieldsWrap.replaceWith(copyTo);

      waActiveSettingsForm();
    });

    $('.jetexir-move-down-repeatable').unbind("click").on('click', function (e) {
      e.preventDefault();

      let $this = $(this), waRepeatableFieldsWrap = $this.closest('.jetexir-repeatable-fields-wrap'),
        waNextRepeatableFieldsWrap = waRepeatableFieldsWrap.next('.jetexir-repeatable-fields-wrap');

      let copyTo = waNextRepeatableFieldsWrap.clone(true), copyFrom = waRepeatableFieldsWrap.clone(true);
      waNextRepeatableFieldsWrap.replaceWith(copyFrom);
      waRepeatableFieldsWrap.replaceWith(copyTo);

      waActiveSettingsForm();
    });
  }

  setTimeout(function () {
    $('.jetexir-repeatable').each(function () {
      let waRepeatableFieldsWrap = $(this).find('.jetexir-repeatable-fields-wrap');

      if (waRepeatableFieldsWrap !== undefined && waRepeatableFieldsWrap.length > 1) {
        $(this).find('.jetexir-remove-repeatable').show();
        $(this).find('.jetexir-move-up-repeatable').show();
        $(this).find('.jetexir-move-down-repeatable').show();
      }
    });

    waRepeatableInit();
  }, 500);

  function waSortableElement(elm, sortCallback = null) {
    elm.find('tbody').sortable({
      items: 'tr',
      cursor: 'move',
      axis: 'y',
      handle: '.sort',
      scrollSensitivity: 40,
      helper: function (e, ui) {
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
  function waDataTableUiModal($this, waDataTableID, modalTarget) {
    if (modalTarget !== undefined) {
      let modalTargetElm = $(modalTarget);
      if (modalTargetElm.length) {
        let displayActiveField = parseInt($this.data('display-active-field')),
          activeField = parseInt($this.data('active-field'));

        modalTargetElm.attr('data-dtu-id', waDataTableID);
        modalTargetElm.find('#jetexir-toggle-dtu-row-active').prop('checked', activeField === 1);
        if (displayActiveField === 1)
          modalTargetElm.find('.jetexir-modal-footer .jetexir-field-toggle').show();
        else
          modalTargetElm.find('.jetexir-modal-footer .jetexir-field-toggle').hide();

        modalTargetElm.find('.jetexir-modal-message').html('');
        modalTargetElm.find('.jetexir-modal-footer').hide();
        modalTargetElm.find('.jetexir-modal-footer .jetexir-button-primary').html($this.data('primary-button-text'));
        modalTargetElm.find('.jetexir-modal-title').html($this.data('modal-title'));
        modalTargetElm.find('.jetexir-modal-body').html('<div class="jetexir-loader-wrap"><div class="jetexir-loader"></div></div>');
      }
    }
  }

  function waDataTableUiInit() {
    // Data table action buttons
    $('.jetexir-data-table-ui .jetexir-dtu-action').on('click', function (e) {
      e.preventDefault();

      let $this = $(this),
        waDataTable = $this.closest('.jetexir-data-table-ui'),
        waDataTableID = waDataTable.data('id'),
        waDataTableRow = $this.closest('tr'),
        waDataTableRowId = waDataTableRow.data('id'),
        waDataTableAction = $this.data('action'),
        waDataTableActionType = $this.data('action-type'),
        waDataTableBody = waDataTable.find('.jetexir-dtu-body'),
        waDataTableTable = waDataTable.find('.jetexir-dtu-table'),
        waDataTableRowCount = waDataTable.find('.jetexir-dtu-row-count'),
        modalTarget = $this.data('jetexir-target'),
        modalTargetElm = $(modalTarget);

      if (jetexirAjax || waDataTableActionType === 'delete' && !confirm(Jetexir.dtuConfirmDelete)) {
        return;
      }

      jetexirAjax = true;

      waDataTableUiModal($this, waDataTableID, modalTarget);

      $.post(
        Jetexir.ajaxUrl,
        {
          nonce: Jetexir.ajaxNonce,
          action: 'jetexir_data_table_ui_action',
          data_table_id: waDataTableID,
          row_id: waDataTableRowId,
          row_action: waDataTableAction
        }
      )
        .done(function (data) {
          if (waDataTableActionType === 'delete') {
            waDataTableRow.fadeTo(200, 0.01, () => {
              waDataTableRow.children('td, th')
                .animate({padding: 0})
                .wrapInner('<div />')
                .children()
                .slideUp(200, () => {
                  waDataTableRow.remove();

                  if (data?.data?.table && data?.data.table !== '') {
                    waDataTableTable.replaceWith(data?.data.table);

                    if (waDataTableRowCount.length && data?.data?.row_count)
                      waDataTableRowCount.html(data?.data?.row_count)

                    waModalInit(waDataTableBody);
                    waDataTableUiInit();
                  }
                });
            });

          } else if (waDataTableActionType === 'edit') {
            modalTargetElm.find('.jetexir-modal-footer').show();
            modalTargetElm.find('.jetexir-modal-body').html(data.data.content);

            setTimeout(function () {
              waWpColorPickerInit();
              waInitGradient();
            }, 500);
          }

          waCopyTextInit();

          if (data.data?.redirect && data.data.redirect !== '')
            window.location.href = data.data.redirect;

          if (data.data?.refresh)
            window.location.reload(true);
        })
        .fail(function (xhr, status, error) {
          if (xhr.responseJSON?.data?.refresh)
            setTimeout(function () {
              window.location.reload(true);
            }, 3000);
        })
        .always(function () {
          jetexirAjax = false;
        });
    });

    $('.jetexir-data-table-sortable').each(function () {
      waSortableElement($(this).find('.jetexir-dtu-table'), function (event, ui) {
        if (event.type === 'sortstop') {
          $(event.target).closest('.jetexir-data-table-ui').find('button.jetexir-dtu-save-changes').prop('disabled', false);
        }
      });
    });
  }

  // Data Table UI save rows changes
  $('.jetexir-data-table-ui button.jetexir-dtu-save-changes').on('click', function () {
    let $this = $(this),
      waDataTable = $this.closest('.jetexir-data-table-ui'),
      waDataTableID = waDataTable.data('id'),
      waDataTableBody = waDataTable.find('.jetexir-dtu-body'),
      waDataTableRowCount = waDataTable.find('.jetexir-dtu-row-count'),
      waDataTableTable = waDataTable.find('.jetexir-dtu-table'),
      waDataTableRowOrders = {};

    if (jetexirAjax) {
      return;
    }
    jetexirAjax = true;

    waDataTableTable.find('.jetexir-dtu-row-order').each(function () {
      waDataTableRowOrders[$(this).closest('tr').data('id')] = parseInt($(this).val());
    });

    if (waDataTableRowOrders.length === 0)
      return;

    $.post(
      Jetexir.ajaxUrl,
      {
        nonce: Jetexir.ajaxNonce,
        action: 'jetexir_data_table_ui_action',
        data_table_id: waDataTableID,
        row_id: -1,
        row_action: 'save_changes',
        row_orders: waDataTableRowOrders
      }
    )
      .done(function (data) {
        if (data?.data?.table && data?.data.table !== '') {
          waDataTableTable.replaceWith(data?.data.table);

          if (waDataTableRowCount.length && data?.data?.row_count)
            waDataTableRowCount.html(data?.data?.row_count)

          waModalInit(waDataTableBody);
          waDataTableUiInit();
        }

        waDataTable.find('button.jetexir-dtu-save-changes').prop('disabled', true);

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
        jetexirAjax = false;
      });
  });

  $('.jetexir-dtu-bulk-actions button').on('click', function () {
    let $this = $(this),
      waDataTableBulkActions = $this.closest('.jetexir-dtu-bulk-actions'),
      waDataTableBulkAction = waDataTableBulkActions.find('select').val(),
      waDataTableActionType, waDataTableRowsSelected = [],
      waDataTable = $this.closest('.jetexir-data-table-ui'),
      waDataTableID = waDataTable.data('id'),
      waDataTableBody = waDataTable.find('.jetexir-dtu-body'),
      waDataTableRowCount = waDataTable.find('.jetexir-dtu-row-count'),
      waDataTableTable = waDataTable.find('.jetexir-dtu-table');

    if (waDataTableBulkAction.length === 0)
      return;

    waDataTableActionType = waDataTableBulkActions.find('select option[value="' + waDataTableBulkAction + '"]').data('action-type');
    if (jetexirAjax || waDataTableActionType === 'delete' && !confirm(Jetexir.dtuConfirmDelete)) {
      return;
    }

    waDataTableTable.find('.jetexir-dtu-row-select:checked').each(function () {
      waDataTableRowsSelected.push(parseInt($(this).val()));
    });

    if (waDataTableRowsSelected.length === 0)
      return;

    jetexirAjax = true;

    $.post(
      Jetexir.ajaxUrl,
      {
        nonce: Jetexir.ajaxNonce,
        action: 'jetexir_data_table_ui_action',
        data_table_id: waDataTableID,
        row_id: -1,
        row_action: 'bulk_action',
        bulk_action: waDataTableBulkAction,
        row_ids: waDataTableRowsSelected
      }
    )
      .done(function (data) {
        if (data?.data?.table && data?.data.table !== '') {
          waDataTableTable.replaceWith(data?.data.table);

          if (waDataTableRowCount.length && data?.data?.row_count)
            waDataTableRowCount.html(data?.data?.row_count)

          waModalInit(waDataTableBody);
          waDataTableUiInit();
        }

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
        jetexirAjax = false;
      });
  });

  // Add new button click event
  $('.jetexir-data-table-ui .jetexir-dtu-add-new').on('click', function () {
    if (jetexirAjax) return;
    jetexirAjax = true;

    let $this = $(this),
      waDataTable = $this.closest('.jetexir-data-table-ui'),
      waDataTableID = waDataTable.data('id'),
      modalTarget = $this.data('jetexir-target'),
      modalTargetElm = $(modalTarget);

    waDataTableUiModal($this, waDataTableID, modalTarget);

    $.post(
      Jetexir.ajaxUrl,
      {
        nonce: Jetexir.ajaxNonce,
        action: 'jetexir_data_table_ui_action',
        data_table_id: waDataTableID,
        row_id: -1,
        row_action: 'add_form'
      }
    )
      .done(function (data) {
        modalTargetElm.find('.jetexir-modal-footer').show();
        modalTargetElm.find('.jetexir-modal-body').html(data.data.content);

        setTimeout(function () {
          waWpColorPickerInit();
          waInitGradient();
        }, 500);

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
        jetexirAjax = false;
      });
  });

  // DataTableUI modal submit
  $('.jetexir-data-table-ui-modal .jetexir-modal-footer button.jetexir-button-primary').unbind('click').on('click', function () {
    let $this = $(this),
      waModal = $this.closest('.jetexir-modal'),
      waDataTableID = waModal.data('dtu-id'),
      waDataTable = $('.jetexir-data-table-ui[data-id="' + waDataTableID + '"]'),
      rowActive = waModal.find('input[name="jetexir_dtu-row-active"]').is(':checked'),
      rowId = waModal.find('input[name="jetexir_row_id"]').val(),
      waModalBody = waModal.find('.jetexir-modal-body'),
      waModalMessage = waModal.find('.jetexir-modal-message'),
      waCloseButton = waModal.find('.jetexir-button-close'),
      waDataTableBody = waDataTable.find('.jetexir-dtu-body'),
      waDataTableTable = waDataTable.find('.jetexir-dtu-table'),
      waDataTableRowCount = waDataTable.find('.jetexir-dtu-row-count');

    $.post(
      Jetexir.ajaxUrl,
      {
        nonce: Jetexir.ajaxNonce,
        action: 'jetexir_data_table_ui_action',
        data_table_id: waDataTableID,
        row_id: parseInt(rowId),
        row_action: 'save_form',
        row_active: rowActive ? 1 : 0,
        form_data: waModalBody.serialize()
      }
    )
      .done(function (data) {
        if (data?.data?.message && data?.data.message !== '') {
          waModalMessage.html(data?.data.message);
        }

        if (data.data?.redirect && data.data.redirect !== '')
          window.location.href = data.data.redirect;

        if (data?.data?.table && data?.data.table !== '') {
          waDataTableTable.replaceWith(data?.data.table);

          if (waDataTableRowCount.length && data?.data?.row_count)
            waDataTableRowCount.html(data?.data?.row_count)

          waModalInit(waDataTableBody);
          waDataTableUiInit();
        }

        setTimeout(function () {
          waCloseButton.trigger('click');

          if (data.data?.refresh)
            window.location.reload(true);
        }, 2000);
      })
      .fail(function (xhr, status, error) {
        if (xhr.responseJSON?.data?.message && xhr.responseJSON?.data.message !== '') {
          waModalMessage.html(xhr.responseJSON?.data.message);
        }

        if (xhr.responseJSON?.data?.refresh)
          setTimeout(function () {
            window.location.reload(true);
          }, 3000);
      })
      .always(function () {
        waModal.animate({scrollTop: 0}, "slow");
        jetexirAjax = false;
      });
  });

  waDataTableUiInit();


  /** Media methods */
  function waMediaInit() {
    $('.jetexir-media-image').unbind('click').on('click', function () {
      let $this = $(this),
        mediaSelectID = $this.attr('data-id'),
        mediaWrap = $this.closest('.jetexir-media-wrap'),
        mediaInput = mediaWrap.find('input'),
        mediaImageIDs = mediaInput.val().split(',');

      const index = mediaImageIDs.indexOf(mediaSelectID);
      if (index > -1) {
        mediaImageIDs.splice(index, 1);
      }

      if (mediaImageIDs.length === 0) {
        mediaWrap.removeClass('jetexir-media-selected');
      }

      mediaInput.val(mediaImageIDs.join(','));
      $this.remove();
      waActiveSettingsForm();
    });
  }

  $('.jetexir-media-select').on('click', function () {
    let $this = $(this),
      mediaWrap = $this.closest('.jetexir-media-wrap'),
      mediaWrapperID = mediaWrap.attr('id'),
      mediaTitle = mediaWrap.data('title'),
      mediaButton = mediaWrap.data('button'),
      mediaType = mediaWrap.data('type'),
      acceptExtensions = mediaWrap.data('accept-extensions'),
      multiSelection = parseInt(mediaWrap.data('multi-selection')) === 1,
      mediaMaxNumber = parseInt(mediaWrap.data('max-number')),
      mediaMultiple = mediaMaxNumber > 1,
      mediaImageContainer = mediaWrap.find('.jetexir-media-images'),
      mediaInput = mediaWrap.find('input'),
      mediaSelected = 1;

    /*if (waWpMediaFrames.hasOwnProperty(mediaWrapperID)) {
        waWpMediaFrames[mediaWrapperID].open();
        return;
    }*/

    // Create a new media frame
    waWpMediaFrames[mediaWrapperID] = wp.media({
      title: mediaTitle,
      button: {
        text: mediaButton
      },
      library: {
        type: mediaType
      },
      multiple: mediaMultiple
    });

    waWpMediaFrames[mediaWrapperID].once('uploader:ready', function () {
      var uploader = waWpMediaFrames[mediaWrapperID].uploader.uploader.uploader; // Upload manager

      //Updating allowed extensions
      uploader.setOption('filters',
        {
          mime_types: [
            {extensions: acceptExtensions}
          ]
        }
      );

      //Trick to reinit field
      uploader.setOption('multi_selection', multiSelection);
    });

    waWpMediaFrames[mediaWrapperID].on('open', function () {
      let selection = waWpMediaFrames[mediaWrapperID].state().get('selection'),
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
    waWpMediaFrames[mediaWrapperID].on('select', function () {
      mediaImageContainer.html('');

      // Get media attachment details from the frame state
      let attachments = waWpMediaFrames[mediaWrapperID].state().get('selection'),
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

      waMediaInit();
      waActiveSettingsForm();
    });

    // Finally, open the modal on click
    waWpMediaFrames[mediaWrapperID].open();
  });

  $('.jetexir-media-remove-all').on('click', function () {
    let $this = $(this),
      mediaWrap = $this.closest('.jetexir-media-wrap'),
      mediaInput = mediaWrap.find('input');

    mediaWrap.removeClass('jetexir-media-selected');
    mediaInput.val('');
    waActiveSettingsForm();
  });

  waMediaInit();


  /**
   * Copy text
   * */
  function waCopyTextInit() {
    let waCopyText = $('.jetexir-copy-text');
    if (navigator.clipboard) {
      waCopyText.each(function () {
        if ($(this).attr('title') === undefined)
          $(this).attr('title', Jetexir.copyText);
      })
      waCopyText.unbind('click').on('click', function () {
        let waCopyTextElm = $(this),
          waTextForCopy = waCopyTextElm.attr('data-text') !== undefined ? waCopyTextElm.attr('data-text') : waCopyTextElm.text();
        navigator.clipboard.writeText(waTextForCopy);
        waCopyTextElm.addClass('jetexir-text-copied');

        setTimeout(function () {
          waCopyTextElm.removeClass('jetexir-text-copied');
        }, 500);
      });
    } else {
      waCopyText.removeClass('jetexir-copy-text');
    }
  }

  waCopyTextInit();
});
