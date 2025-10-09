jQuery(document).ready(function ($) {
    let wpColorPickerPalettes = ['#333', '#5de0f0', '#608bf7', '#7fff3f', '#00b700', '#fff200', '#ffae63', '#e64f6f', '#ef32e3', '#d1c1ff', '#873eff'],
        wpColorPickerOptions = {
            defaultColor: false, change: function (event, ui) {
                waActiveSettingsForm();
            }, clear: function () {
                waActiveSettingsForm();
            }, hide: true, palettes: wpColorPickerPalettes
        },
        settingsSubmitActive = false,
        wpMediaFrames = {};

    const waBody = $('body'),
        waContentWrap = $('#asfowoo-content-wrap'),
        waSettingsHeader = $('#asfowoo-settings-header'),
        waSettingsSidebar = $('#asfowoo-sidebar'),
        waSettingsDisplaySidebar = $('#asfowoo-display-sidebar'),
        waSettingsHideSidebar = $('#asfowoo-hide-sidebar'),
        waSettingsSectionLinks = $('.asfowoo-section-links ul'),
        settingsForm = document.getElementById('asfowoo-settings-form'),
        settingsFooter = document.getElementById('asfowoo-settings-footer'),
        settingsResetButton = document.getElementById("asfowoo-settings-reset-button");

    let waContentWrapPrevScrollPos = waContentWrap.scrollTop(),
        waContentWrapCurrentScrollPos = waContentWrapPrevScrollPos;

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
        waSettingsSidebar.addClass('asfowoo-mobile-sidebar');
        waBody.addClass('asfowoo-mobile-sidebar-active');
    });
    waSettingsHideSidebar.on('click', function (e) {
        e.preventDefault();
        waSettingsSidebar.removeClass('asfowoo-mobile-sidebar');
        waBody.removeClass('asfowoo-mobile-sidebar-active');
    });

    /**
     * Auto scroll to active section link
     * */
    if (waSettingsSectionLinks.length) {
        let waSectionActiveLink = waSettingsSectionLinks.find('.asfowoo-section-link-current'),
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
    assistantForWooCommerceModalCloseEvent = new CustomEvent(
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
        let modalOverlay = $('#asfowoo-modal-overlay'),
            modalTarget = waBody.attr('data-asfowoo-modal-target');

        if (status && !waBody.hasClass('asfowoo-modal-open')) {
            waBody.css({
                "overflow": "hidden",
                "padding-right": "0"
            })
                .addClass('asfowoo-modal-open')
                .attr('data-asfowoo-modal-target', target);
            $(target).toggleClass('asfowoo-active').removeAttr('aria-hidden').show();
            if (modalOverlay !== undefined)
                modalOverlay.addClass('asfowoo-active');

        } else if (!status && waBody.hasClass('asfowoo-modal-open')) {
            window.dispatchEvent(assistantForWooCommerceModalCloseEvent);

            waBody.css({
                "overflow": "",
                "padding-right": ""
            })
                .removeClass('asfowoo-modal-open')
                .removeAttr('data-asfowoo-modal-target');
            $(modalTarget).hide().removeClass('asfowoo-active').attr('aria-hidden', 'true');
            if (modalOverlay !== undefined)
                modalOverlay.removeClass('asfowoo-active');
        }
    }

    function waModalInit(wrapper) {
        wrapper.find('[data-asfowoo-toggle="modal"]').unbind('click').on('click', function () {
            let $this = $(this),
                modalTarget = $this.data('asfowoo-target');

            if (modalTarget !== undefined) {
                let modalTargetElm = $(modalTarget);
                if (modalTargetElm.length) {
                    waToggleModal(true, modalTarget);
                }
            }
        });
    }

    waModalInit(waBody);
    $('#asfowoo-modal-overlay, [data-asfowoo-dismiss="modal"]').on('click', function () {
        waToggleModal(false);
    });


    function waActiveSettingsForm() {
        if (settingsSubmitActive) return;
        settingsSubmitActive = true;

        if (settingsFooter) settingsFooter.classList.remove('asfowoo-submit-inactive');
    }

    if (settingsForm) {
        if (settingsFooter) settingsFooter.classList.add('asfowoo-submit-inactive');

        settingsForm.addEventListener('change', function () {
            waActiveSettingsForm();
        });

        if (settingsResetButton) {
            settingsResetButton.addEventListener("click", () => {
                settingsSubmitActive = false;

                if (settingsFooter) settingsFooter.classList.add('asfowoo-submit-inactive');
            });
        }
    }

    function waInitGradient() {
        const wpGradientSelectColor = $('.asfowoo-gradient-select-color input[type="text"]');
        if (wpGradientSelectColor.length) {
            wpGradientSelectColor.wpColorPicker({
                defaultColor: false, change: function (event, ui) {
                    let gradientWrap = $(event.target).closest('.asfowoo-gradient-color-picker-wrap'),
                        gradientContainer = gradientWrap.find('.asfowoo-gradient-color-picker'),
                        selectedColor = ui.color.toString();

                    gradientContainer.find('.asfowoo-gradient-color-point.is-active').attr('data-color', selectedColor);
                    gradientContainer.find('.asfowoo-gradient-color-point.is-active span').css('background-color', selectedColor);
                    waUpdateGradient(gradientWrap);
                    waActiveSettingsForm();

                }, clear: function () {
                    waActiveSettingsForm();
                }, hide: true, palettes: wpColorPickerPalettes
            });
        }

        $('.asfowoo-gradient-color-picker-wrap').not('.asfowoo-gradient-color-picker-initialized').each(function () {
            let gradientWrap = $(this),
                gradientField = gradientWrap.find('input.asfowoo-gradient-color-picker-value[type="hidden"]'),
                gradientContainer = gradientWrap.find('.asfowoo-gradient-color-picker'),
                gradientSelectColor = gradientWrap.find('.asfowoo-gradient-select-color'),
                gradientInfo = JSON.parse(gradientField.val().replaceAll("'", '"')),
                gradientPoint, gradientPointX, maxX, minX = 5, minY = 5, pX,
                gradientPoints = Object.entries(gradientInfo.colors);

            gradientWrap.addClass('asfowoo-gradient-color-picker-initialized');
            gradientSelectColor.append('<a href="#" class="asfowoo-gradient-remove-color" style="display: ' + (gradientPoints.length > 2 ? 'block' : 'none') + '">' + AssistantForWooCommerce.removeText + '</a>');

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

        $('.asfowoo-gradient-remove-color').unbind('click').on('click', function (e) {
            e.preventDefault();
            let gradientWrap = $(this).closest('.asfowoo-gradient-color-picker-wrap'),
                gradientPoints = gradientWrap.find('.asfowoo-gradient-color-point');
            if (gradientPoints.length <= 2)
                return;

            gradientWrap.find('.asfowoo-gradient-color-point.is-active').remove();
            gradientPoints = gradientWrap.find('.asfowoo-gradient-color-point');
            if (gradientPoints.length <= 2)
                $(this).hide();

            gradientWrap.find('.asfowoo-gradient-color-point').first().addClass('is-active').trigger('click');

            waUpdateGradient(gradientWrap);
            waActiveSettingsForm();
        });

        $('.asfowoo-gradient-color-picker-wrap .asfowoo-gradient-color-picker').unbind('click').on('click', function (e) {
            if (!$(e.target).is('.asfowoo-gradient-color-picker'))
                return;

            var gradientContainer = $(this),
                gradientWrap = gradientContainer.closest('.asfowoo-gradient-color-picker-wrap'),
                gradientMaxColors = parseInt(gradientWrap.data('max-colors')),
                gradientPoints = gradientWrap.find('.asfowoo-gradient-color-point'),
                maxX, minX = 5, pX;

            if (gradientMaxColors <= gradientPoints.length)
                return;

            gradientPoints.removeClass('is-active');

            var gradientWrapID = gradientWrap.attr('id'),
                leftX = e.pageX - gradientContainer.offset().left,
                gradientPointFirst = gradientContainer.find('.asfowoo-gradient-color-point').first(),
                gradientPoint = gradientPointFirst.clone(),
                gradientPointX, minY = gradientPointFirst.css('top'),
                gradientRemove = gradientWrap.find('.asfowoo-gradient-remove-color'),
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

            gradientWrap.find('.asfowoo-gradient-color-point').each(function (index) {
                $(this).attr('id', gradientWrapID + '-' + index);
                $(this).attr('data-index', index);
            });

            gradientPointX = document.getElementById(gradientWrapID + '-' + gradientPoint.attr('data-index'));
            waGradientPointDrag(gradientPointX, minX, maxX, minY);
            waInitGradient();
            gradientPoint.trigger('click');
        });

        $('.asfowoo-gradient-color-picker-wrap .asfowoo-gradient-color-rotation .asfowoo-input-range').unbind('input').on('input', function () {
            waUpdateGradient($(this).closest('.asfowoo-gradient-color-picker-wrap'));
        });

        $('.asfowoo-gradient-color-picker-wrap .asfowoo-gradient-color-shape input[type="radio"]').unbind('click').on('click', function () {
            waUpdateGradient($(this).closest('.asfowoo-gradient-color-picker-wrap'));
        });

        $('.asfowoo-gradient-color-picker-wrap .asfowoo-gradient-color-type input[type="radio"]').unbind('click').on('click', function () {
            let gradientWrap = $(this).closest('.asfowoo-gradient-color-picker-wrap');

            gradientWrap.find('.asfowoo-gradient-color-variant').hide();

            if ($(this).val() === 'linear-gradient') {
                gradientWrap.find('.asfowoo-gradient-color-rotation').show();

            } else if ($(this).val() === 'radial-gradient') {
                gradientWrap.find('.asfowoo-gradient-color-shape').show();
            }

            waUpdateGradient(gradientWrap);
        });

        $('.asfowoo-gradient-color-picker-wrap .asfowoo-gradient-color-point').unbind('click').on('click', function () {
            let gradientWrap = $(this).closest('.asfowoo-gradient-color-picker-wrap');
            gradientWrap.find('.asfowoo-gradient-color-point').removeClass('is-active');
            $(this).addClass('is-active');
            gradientWrap.find('.asfowoo-wp-color-picker input.wp-color-picker').val($(this).attr('data-color')).trigger('change');
        });
    }

    function waGradientPointDrag(elm, minX, maxX, minY) {
        waDrag.init(elm, null, minX, maxX, minY, minY);
        maxX -= minX;

        elm.onDrag = function (elm, x, y) {
            x -= minX;
            let pX = Math.round(x / maxX * 100 * 100) / 100;
            elm.setAttribute('data-position', pX);

            waUpdateGradient($(elm.closest('.asfowoo-gradient-color-picker-wrap')));
            waActiveSettingsForm();
        }
    }

    function waUpdateGradient(gradientWrap) {
        let gradientContainer = gradientWrap.find('.asfowoo-gradient-color-picker'),
            gradientField = gradientWrap.find('input.asfowoo-gradient-color-picker-value[type="hidden"]'),
            gradientPoints = gradientContainer.find('.asfowoo-gradient-color-point'),
            gradientRotation = gradientWrap.find('.asfowoo-input-range').val(),
            gradientType = gradientWrap.find('.asfowoo-gradient-color-type input[type="radio"]:checked').val(),
            gradientShape = gradientWrap.find('.asfowoo-gradient-color-shape input[type="radio"]:checked').val(),
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

    function wpColorPickerInit() {
        let wpColorPicker = $('.asfowoo-wp-color-picker,.asfowoo-color-palette').not('.asfowoo-gradient-select-color').find('input[type="text"]');

        if (wpColorPicker.length) {
            wpColorPicker.wpColorPicker(wpColorPickerOptions);

            setTimeout(function () {
                $('.asfowoo-color-palette[data-removable="1"]').each(function () {
                    let waPickerContainer = $(this).find('.wp-picker-container');

                    if (waPickerContainer.length > 0) {
                        waPickerContainer.append('<button type="button" class="asfowoo-remove-color"><i class="asfowoo-icon-cross"></i></button>');
                    }
                });

                waColorPaletteInit();
            }, 500);
        }
    }

    wpColorPickerInit();

    $('.asfowoo-color-palette .asfowoo-add-color').unbind("click").on('click', function (e) {
        e.preventDefault();

        let $this = $(this),
            waColorPalette = $this.closest('.asfowoo-color-palette'),
            waColorPaletteItems = waColorPalette.find('.asfowoo-color-palette-items'),
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

        waColorInputClone.wpColorPicker(wpColorPickerOptions);

        if (waColorPaletteMax !== undefined && parseInt(waColorPaletteMax) <= (currentColorCount + 1)) {
            $this.attr('disable', 'true');
        }

        if (waColorPalette.attr('data-removable') === '1') {
            waColorPalette.find('.asfowoo-remove-color').show();
            waColorInputClone.closest('.wp-picker-container').append('<button type="button" class="asfowoo-remove-color"><i class="asfowoo-icon-cross"></i></button>');
        }

        waColorPaletteInit();
        waActiveSettingsForm();
    });

    $('.asfowoo-add-repeatable').unbind("click").on('click', function (e) {
        e.preventDefault();

        let $this = $(this), repeatablePosition = $this.data('position'),
            waRepeatable = $this.closest('.asfowoo-repeatable'), repeatableMax = waRepeatable.data('max-repeat'),
            waRepeatableFieldsWrap = waRepeatable.find('.asfowoo-repeatable-fields-wrap'),
            waRepeatableFirstFieldsWrap = waRepeatable.find('.asfowoo-repeatable-fields-wrap').first(),
            waRepeatableLastFieldsWrap = waRepeatable.find('.asfowoo-repeatable-fields-wrap').last(),
            waRepeatableCloneFieldsWrap;

        if (repeatableMax !== undefined && waRepeatableFieldsWrap.length >= parseInt(repeatableMax)) {
            return;
        }

        if (repeatableMax !== undefined && waRepeatableFieldsWrap.length + 1 >= parseInt(repeatableMax)) {
            waRepeatable.find('.asfowoo-add-repeatable').attr('disable', 'true');
        }

        if (waRepeatableFieldsWrap.length >= 1) {
            waRepeatable.find('.asfowoo-remove-repeatable').show();
            waRepeatable.find('.asfowoo-move-up-repeatable').show();
            waRepeatable.find('.asfowoo-move-down-repeatable').show();
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
        $('.asfowoo-color-palette .asfowoo-remove-color').unbind("click").on('click', function (e) {
            let $this = $(this),
                waColorPalette = $this.closest('.asfowoo-color-palette');

            $this.closest('.wp-picker-container').slideUp("normal", function () {
                $(this).remove();

                waColorPalette.find('.asfowoo-add-color').removeAttr('disable');

                if (waColorPalette.find('.wp-picker-container').length <= 1) {
                    waColorPalette.find('.asfowoo-remove-color').hide();
                }

                waActiveSettingsForm();
            });
        });
    }

    function waRepeatableInit() {
        $('.asfowoo-remove-repeatable').unbind("click").on('click', function (e) {
            e.preventDefault();
            let $this = $(this), waRepeatable = $this.closest('.asfowoo-repeatable');

            //$this.closest('.asfowoo-repeatable-fields-wrap').remove();
            $this.closest('.asfowoo-repeatable-fields-wrap').slideUp("normal", function () {
                $(this).remove();

                waRepeatable.find('.asfowoo-add-repeatable').removeAttr('disable');

                if (waRepeatable.find('.asfowoo-repeatable-fields-wrap').length <= 1) {
                    waRepeatable.find('.asfowoo-remove-repeatable').hide();
                    waRepeatable.find('.asfowoo-move-up-repeatable').hide();
                    waRepeatable.find('.asfowoo-move-down-repeatable').hide();
                }

                waActiveSettingsForm();
            });
        });

        $('.asfowoo-move-up-repeatable').unbind("click").on('click', function (e) {
            e.preventDefault();

            let $this = $(this), waRepeatableFieldsWrap = $this.closest('.asfowoo-repeatable-fields-wrap'),
                waPrevRepeatableFieldsWrap = waRepeatableFieldsWrap.prev('.asfowoo-repeatable-fields-wrap');

            let copyTo = waPrevRepeatableFieldsWrap.clone(true), copyFrom = waRepeatableFieldsWrap.clone(true);
            waPrevRepeatableFieldsWrap.replaceWith(copyFrom);
            waRepeatableFieldsWrap.replaceWith(copyTo);

            waActiveSettingsForm();
        });

        $('.asfowoo-move-down-repeatable').unbind("click").on('click', function (e) {
            e.preventDefault();

            let $this = $(this), waRepeatableFieldsWrap = $this.closest('.asfowoo-repeatable-fields-wrap'),
                waNextRepeatableFieldsWrap = waRepeatableFieldsWrap.next('.asfowoo-repeatable-fields-wrap');

            let copyTo = waNextRepeatableFieldsWrap.clone(true), copyFrom = waRepeatableFieldsWrap.clone(true);
            waNextRepeatableFieldsWrap.replaceWith(copyFrom);
            waRepeatableFieldsWrap.replaceWith(copyTo);

            waActiveSettingsForm();
        });
    }

    setTimeout(function () {
        $('.asfowoo-repeatable').each(function () {
            let waRepeatableFieldsWrap = $(this).find('.asfowoo-repeatable-fields-wrap');

            if (waRepeatableFieldsWrap !== undefined && waRepeatableFieldsWrap.length > 1) {
                $(this).find('.asfowoo-remove-repeatable').show();
                $(this).find('.asfowoo-move-up-repeatable').show();
                $(this).find('.asfowoo-move-down-repeatable').show();
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
                $('input.asfowoo-dtu-row-order', tr).val(parseInt(index));
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
                modalTargetElm.find('#asfowoo-toggle-dtu-row-active').prop('checked', activeField === 1);
                if (displayActiveField === 1)
                    modalTargetElm.find('.asfowoo-modal-footer .asfowoo-field-toggle').show();
                else
                    modalTargetElm.find('.asfowoo-modal-footer .asfowoo-field-toggle').hide();

                modalTargetElm.find('.asfowoo-modal-message').html('');
                modalTargetElm.find('.asfowoo-modal-footer').hide();
                modalTargetElm.find('.asfowoo-modal-footer .asfowoo-button-primary').html($this.data('primary-button-text'));
                modalTargetElm.find('.asfowoo-modal-title').html($this.data('modal-title'));
                modalTargetElm.find('.asfowoo-modal-body').html('<div class="asfowoo-loader-wrap"><div class="asfowoo-loader"></div></div>');
            }
        }
    }

    function waDataTableUiInit() {
        // Data table action buttons
        $('.asfowoo-data-table-ui .asfowoo-dtu-action').on('click', function (e) {
            e.preventDefault();

            let $this = $(this),
                waDataTable = $this.closest('.asfowoo-data-table-ui'),
                waDataTableID = waDataTable.data('id'),
                waDataTableRow = $this.closest('tr'),
                waDataTableRowId = waDataTableRow.data('id'),
                waDataTableAction = $this.data('action'),
                waDataTableActionType = $this.data('action-type'),
                waDataTableBody = waDataTable.find('.asfowoo-dtu-body'),
                waDataTableTable = waDataTable.find('.asfowoo-dtu-table'),
                waDataTableRowCount = waDataTable.find('.asfowoo-dtu-row-count'),
                modalTarget = $this.data('asfowoo-target'),
                modalTargetElm = $(modalTarget);

            if (assistantForWooCommerceAjax || waDataTableActionType === 'delete' && !confirm(AssistantForWooCommerce.dtuConfirmDelete)) {
                return;
            }

            assistantForWooCommerceAjax = true;

            waDataTableUiModal($this, waDataTableID, modalTarget);

            $.post(
                AssistantForWooCommerce.ajaxUrl,
                {
                    nonce: AssistantForWooCommerce.ajaxNonce,
                    action: 'assistant_for_woocommerce_data_table_ui_action',
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
                        modalTargetElm.find('.asfowoo-modal-footer').show();
                        modalTargetElm.find('.asfowoo-modal-body').html(data.data.content);

                        setTimeout(function () {
                            wpColorPickerInit();
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
                    assistantForWooCommerceAjax = false;
                });
        });

        $('.asfowoo-data-table-sortable').each(function () {
            waSortableElement($(this).find('.asfowoo-dtu-table'), function (event, ui) {
                if (event.type === 'sortstop') {
                    $(event.target).closest('.asfowoo-data-table-ui').find('button.asfowoo-dtu-save-changes').prop('disabled', false);
                }
            });
        });
    }

    // Data Table UI save rows changes
    $('.asfowoo-data-table-ui button.asfowoo-dtu-save-changes').on('click', function () {
        let $this = $(this),
            waDataTable = $this.closest('.asfowoo-data-table-ui'),
            waDataTableID = waDataTable.data('id'),
            waDataTableBody = waDataTable.find('.asfowoo-dtu-body'),
            waDataTableRowCount = waDataTable.find('.asfowoo-dtu-row-count'),
            waDataTableTable = waDataTable.find('.asfowoo-dtu-table'),
            waDataTableRowOrders = {};

        if (assistantForWooCommerceAjax) {
            return;
        }
        assistantForWooCommerceAjax = true;

        waDataTableTable.find('.asfowoo-dtu-row-order').each(function () {
            waDataTableRowOrders[$(this).closest('tr').data('id')] = parseInt($(this).val());
        });

        if (waDataTableRowOrders.length === 0)
            return;

        $.post(
            AssistantForWooCommerce.ajaxUrl,
            {
                nonce: AssistantForWooCommerce.ajaxNonce,
                action: 'assistant_for_woocommerce_data_table_ui_action',
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

                waDataTable.find('button.asfowoo-dtu-save-changes').prop('disabled', true);

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
                assistantForWooCommerceAjax = false;
            });
    });

    $('.asfowoo-dtu-bulk-actions button').on('click', function () {
        let $this = $(this),
            waDataTableBulkActions = $this.closest('.asfowoo-dtu-bulk-actions'),
            waDataTableBulkAction = waDataTableBulkActions.find('select').val(),
            waDataTableActionType, waDataTableRowsSelected = [],
            waDataTable = $this.closest('.asfowoo-data-table-ui'),
            waDataTableID = waDataTable.data('id'),
            waDataTableBody = waDataTable.find('.asfowoo-dtu-body'),
            waDataTableRowCount = waDataTable.find('.asfowoo-dtu-row-count'),
            waDataTableTable = waDataTable.find('.asfowoo-dtu-table');

        if (waDataTableBulkAction.length === 0)
            return;

        waDataTableActionType = waDataTableBulkActions.find('select option[value="' + waDataTableBulkAction + '"]').data('action-type');
        if (assistantForWooCommerceAjax || waDataTableActionType === 'delete' && !confirm(AssistantForWooCommerce.dtuConfirmDelete)) {
            return;
        }

        waDataTableTable.find('.asfowoo-dtu-row-select:checked').each(function () {
            waDataTableRowsSelected.push(parseInt($(this).val()));
        });

        if (waDataTableRowsSelected.length === 0)
            return;

        assistantForWooCommerceAjax = true;

        $.post(
            AssistantForWooCommerce.ajaxUrl,
            {
                nonce: AssistantForWooCommerce.ajaxNonce,
                action: 'assistant_for_woocommerce_data_table_ui_action',
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
                assistantForWooCommerceAjax = false;
            });
    });

    // Add new button click event
    $('.asfowoo-data-table-ui .asfowoo-dtu-add-new').on('click', function () {
        if (assistantForWooCommerceAjax) return;
        assistantForWooCommerceAjax = true;

        let $this = $(this),
            waDataTable = $this.closest('.asfowoo-data-table-ui'),
            waDataTableID = waDataTable.data('id'),
            modalTarget = $this.data('asfowoo-target'),
            modalTargetElm = $(modalTarget);

        waDataTableUiModal($this, waDataTableID, modalTarget);

        $.post(
            AssistantForWooCommerce.ajaxUrl,
            {
                nonce: AssistantForWooCommerce.ajaxNonce,
                action: 'assistant_for_woocommerce_data_table_ui_action',
                data_table_id: waDataTableID,
                row_id: -1,
                row_action: 'add_form'
            }
        )
            .done(function (data) {
                modalTargetElm.find('.asfowoo-modal-footer').show();
                modalTargetElm.find('.asfowoo-modal-body').html(data.data.content);

                setTimeout(function () {
                    wpColorPickerInit();
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
                assistantForWooCommerceAjax = false;
            });
    });

    // DataTableUI modal submit
    $('.asfowoo-data-table-ui-modal .asfowoo-modal-footer button.asfowoo-button-primary').unbind('click').on('click', function () {
        let $this = $(this),
            waModal = $this.closest('.asfowoo-modal'),
            waDataTableID = waModal.data('dtu-id'),
            waDataTable = $('.asfowoo-data-table-ui[data-id="' + waDataTableID + '"]'),
            rowActive = waModal.find('input[name="assistant_for_woocommerce_dtu-row-active"]').is(':checked'),
            rowId = waModal.find('input[name="assistant_for_woocommerce_row_id"]').val(),
            waModalBody = waModal.find('.asfowoo-modal-body'),
            waModalMessage = waModal.find('.asfowoo-modal-message'),
            waCloseButton = waModal.find('.asfowoo-button-close'),
            waDataTableBody = waDataTable.find('.asfowoo-dtu-body'),
            waDataTableTable = waDataTable.find('.asfowoo-dtu-table'),
            waDataTableRowCount = waDataTable.find('.asfowoo-dtu-row-count');

        $.post(
            AssistantForWooCommerce.ajaxUrl,
            {
                nonce: AssistantForWooCommerce.ajaxNonce,
                action: 'assistant_for_woocommerce_data_table_ui_action',
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
                assistantForWooCommerceAjax = false;
            });
    });

    waDataTableUiInit();


    /** Media methods */
    function waMediaInit() {
        $('.asfowoo-media-image').unbind('click').on('click', function () {
            let $this = $(this),
                mediaSelectID = $this.attr('data-id'),
                mediaWrap = $this.closest('.asfowoo-media-wrap'),
                mediaInput = mediaWrap.find('input'),
                mediaImageIDs = mediaInput.val().split(',');

            const index = mediaImageIDs.indexOf(mediaSelectID);
            if (index > -1) {
                mediaImageIDs.splice(index, 1);
            }

            if (mediaImageIDs.length === 0) {
                mediaWrap.removeClass('asfowoo-media-selected');
            }

            mediaInput.val(mediaImageIDs.join(','));
            $this.remove();
            waActiveSettingsForm();
        });
    }

    $('.asfowoo-media-select').on('click', function () {
        let $this = $(this),
            mediaWrap = $this.closest('.asfowoo-media-wrap'),
            mediaWrapperID = mediaWrap.attr('id'),
            mediaTitle = mediaWrap.data('title'),
            mediaButton = mediaWrap.data('button'),
            mediaType = mediaWrap.data('type'),
            acceptExtensions = mediaWrap.data('accept-extensions'),
            multiSelection = parseInt(mediaWrap.data('multi-selection')) === 1,
            mediaMaxNumber = parseInt(mediaWrap.data('max-number')),
            mediaMultiple = mediaMaxNumber > 1,
            mediaImageContainer = mediaWrap.find('.asfowoo-media-images'),
            mediaInput = mediaWrap.find('input'),
            mediaSelected = 1;

        /*if (wpMediaFrames.hasOwnProperty(mediaWrapperID)) {
            wpMediaFrames[mediaWrapperID].open();
            return;
        }*/

        // Create a new media frame
        wpMediaFrames[mediaWrapperID] = wp.media({
            title: mediaTitle,
            button: {
                text: mediaButton
            },
            library: {
                type: mediaType
            },
            multiple: mediaMultiple
        });

        wpMediaFrames[mediaWrapperID].once('uploader:ready', function () {
            var uploader = wpMediaFrames[mediaWrapperID].uploader.uploader.uploader; // Upload manager

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

        wpMediaFrames[mediaWrapperID].on('open', function () {
            let selection = wpMediaFrames[mediaWrapperID].state().get('selection'),
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
        wpMediaFrames[mediaWrapperID].on('select', function () {
            mediaImageContainer.html('');

            // Get media attachment details from the frame state
            let attachments = wpMediaFrames[mediaWrapperID].state().get('selection'),
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
                        mediaImageContainer.append('<div class="asfowoo-media-image" data-id="' + attachment.id + '"><img src="' + attachmentUrl + '" title="' + imageTitle + '"/><span class="asfowoo-media-image-title">' + imageTitle + '</span></div>');
                    }
                    mediaSelected++;
                    return attachment.id;
                });

            attachmentIDs = attachmentIDs.slice(0, mediaMaxNumber);

            mediaWrap.addClass('asfowoo-media-selected');

            // Send the attachment id to our hidden input
            mediaInput.val(attachmentIDs.join(','));

            waMediaInit();
            waActiveSettingsForm();
        });

        // Finally, open the modal on click
        wpMediaFrames[mediaWrapperID].open();
    });

    $('.asfowoo-media-remove-all').on('click', function () {
        let $this = $(this),
            mediaWrap = $this.closest('.asfowoo-media-wrap'),
            mediaInput = mediaWrap.find('input');

        mediaWrap.removeClass('asfowoo-media-selected');
        mediaInput.val('');
        waActiveSettingsForm();
    });

    waMediaInit();


    /**
     * Copy text
     * */
    function waCopyTextInit() {
        let waCopyText = $('.asfowoo-copy-text');
        if (navigator.clipboard) {
            waCopyText.each(function () {
                if ($(this).attr('title') === undefined)
                    $(this).attr('title', AssistantForWooCommerce.copyText);
            })
            waCopyText.unbind('click').on('click', function () {
                let waCopyTextElm = $(this),
                    waTextForCopy = waCopyTextElm.attr('data-text') !== undefined ? waCopyTextElm.attr('data-text') : waCopyTextElm.text();
                navigator.clipboard.writeText(waTextForCopy);
                waCopyTextElm.addClass('asfowoo-text-copied');

                setTimeout(function () {
                    waCopyTextElm.removeClass('asfowoo-text-copied');
                }, 500);
            });
        } else {
            waCopyText.removeClass('asfowoo-copy-text');
        }
    }

    waCopyTextInit();
});