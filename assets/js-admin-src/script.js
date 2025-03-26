jQuery(document).ready(function ($) {
    var wpColorPickerPalettes = [],
        wpColorPickerOptions = {
            defaultColor: false, change: function (event, ui) {
                waActiveSettingsForm();
            }, clear: function () {
                waActiveSettingsForm();
            }, hide: true, palettes: wpColorPickerPalettes
        };
    const settingsForm = document.getElementById('wa-settings-form'),
        settingsFooter = document.getElementById('wa-settings-footer'),
        settingsResetButton = document.getElementById("wa-settings-reset-button");
    let settingsSubmitActive = false;

    function waActiveSettingsForm() {
        if (settingsSubmitActive) return;
        settingsSubmitActive = true;

        if (settingsFooter) settingsFooter.classList.remove('wa-submit-inactive');
    }

    if (settingsForm) {
        if (settingsFooter) settingsFooter.classList.add('wa-submit-inactive');

        settingsForm.addEventListener('change', function () {
            waActiveSettingsForm();
        });

        if (settingsResetButton) {
            settingsResetButton.addEventListener("click", () => {
                settingsSubmitActive = false;

                if (settingsFooter) settingsFooter.classList.add('wa-submit-inactive');
            });
        }
    }

    function waInitGradient() {
        $('.wa-gradient-color-picker-wrap .wa-gradient-color-point').unbind('click').on('click', function () {
            let gradientWrap = $(this).closest('.wa-gradient-color-picker-wrap');
            gradientWrap.find('.wa-gradient-color-point').removeClass('is-active');
            $(this).addClass('is-active');
            gradientWrap.find('.wa-wp-color-picker input.wp-color-picker').val($(this).attr('data-color')).trigger('change');
        });
    }

    function waGradientPointDrag(elm, minX, maxX, minY) {
        waDrag.init(elm, null, minX, maxX, minY, minY);
        maxX -= minX;

        elm.onDrag = function (elm, x, y) {
            x -= minX;
            let pX = Math.round(x / maxX * 100 * 100) / 100;
            elm.setAttribute('data-position', pX);

            waUpdateGradient($(elm.closest('.wa-gradient-color-picker-wrap')));
            waActiveSettingsForm();
        }
    }

    function waUpdateGradient(gradientWrap) {
        let gradientContainer = gradientWrap.find('.wa-gradient-color-picker'),
            gradientField = gradientWrap.find('input.wa-gradient-color-picker-value[type="hidden"]'),
            gradientPoints = gradientContainer.find('.wa-gradient-color-point'),
            gradientRotation = gradientWrap.find('.wa-input-range').val(),
            gradientType = gradientWrap.find('.wa-gradient-color-type input[type="radio"]:checked').val(),
            gradientShape = gradientWrap.find('.wa-gradient-color-shape input[type="radio"]:checked').val(),
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

    const wpGradientSelectColor = $('.wa-gradient-select-color input[type="text"]');
    if (wpGradientSelectColor.length) {
        wpGradientSelectColor.wpColorPicker({
            defaultColor: false, change: function (event, ui) {
                let gradientWrap = $(event.target).closest('.wa-gradient-color-picker-wrap'),
                    gradientContainer = gradientWrap.find('.wa-gradient-color-picker'),
                    selectedColor = gradientWrap.find('.wa-gradient-select-color input.wp-color-picker').val();

                gradientContainer.find('.wa-gradient-color-point.is-active').attr('data-color', selectedColor);
                gradientContainer.find('.wa-gradient-color-point.is-active span').css('background-color', selectedColor);

                setTimeout(function () {
                    waUpdateGradient(gradientWrap);
                }, 300);
                waActiveSettingsForm();
            }, clear: function () {
                waActiveSettingsForm();
            }, hide: true, palettes: wpColorPickerPalettes
        });
    }

    $('.wa-gradient-color-picker-wrap').each(function () {
        let gradientWrap = $(this),
            gradientField = gradientWrap.find('input.wa-gradient-color-picker-value[type="hidden"]'),
            gradientContainer = gradientWrap.find('.wa-gradient-color-picker'),
            gradientSelectColor = gradientWrap.find('.wa-gradient-select-color'),
            gradientInfo = JSON.parse(gradientField.val().replaceAll("'", '"')),
            gradientPoint, gradientPointX, maxX, minX = 5, minY = 5, pX,
            gradientPoints = Object.entries(gradientInfo.colors);

        gradientSelectColor.append('<a href="#" class="wa-gradient-remove-color" style="display: ' + (gradientPoints.length > 2 ? 'block' : 'none') + '">' + WooAssistant.remove_text + '</a>');

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

    $('.wa-gradient-remove-color').on('click', function (e) {
        e.preventDefault();
        let gradientWrap = $(this).closest('.wa-gradient-color-picker-wrap');
        gradientWrap.find('.wa-gradient-color-point.is-active').remove();
        let gradientPoints = gradientWrap.find('.wa-gradient-color-point');
        if (gradientPoints.length <= 2)
            $(this).hide();

        gradientWrap.find('.wa-gradient-color-point').first().addClass('is-active').trigger('click');

        waUpdateGradient(gradientWrap);
        waActiveSettingsForm();
    });

    $('.wa-gradient-color-picker-wrap .wa-gradient-color-picker').on('click', function (e) {
        if (!$(e.target).is('.wa-gradient-color-picker'))
            return;

        var gradientContainer = $(this),
            gradientWrap = gradientContainer.closest('.wa-gradient-color-picker-wrap'),
            maxX, minX = 5, pX;
        gradientWrap.find('.wa-gradient-color-point').removeClass('is-active');

        var gradientWrapID = gradientWrap.attr('id'),
            leftX = e.pageX - gradientContainer.offset().left,
            gradientPointFirst = gradientContainer.find('.wa-gradient-color-point').first(),
            gradientPoint = gradientPointFirst.clone(),
            gradientPointX, minY = gradientPointFirst.css('top'),
            gradientRemove = gradientWrap.find('.wa-gradient-remove-color'),
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

        gradientWrap.find('.wa-gradient-color-point').each(function (index) {
            $(this).attr('id', gradientWrapID + '-' + index);
            $(this).attr('data-index', index);
        });

        gradientPointX = document.getElementById(gradientWrapID + '-' + gradientPoint.attr('data-index'));
        waGradientPointDrag(gradientPointX, minX, maxX, minY);
        waInitGradient();
        gradientPoint.trigger('click');
    });

    $('.wa-gradient-color-picker-wrap .wa-gradient-color-rotation .wa-input-range').on('change', function () {
        waUpdateGradient($(this).closest('.wa-gradient-color-picker-wrap'));
    });

    $('.wa-gradient-color-picker-wrap .wa-gradient-color-shape input[type="radio"]').on('click', function () {
        waUpdateGradient($(this).closest('.wa-gradient-color-picker-wrap'));
    });

    $('.wa-gradient-color-picker-wrap .wa-gradient-color-type input[type="radio"]').on('click', function () {
        let gradientWrap = $(this).closest('.wa-gradient-color-picker-wrap');

        gradientWrap.find('.wa-gradient-color-variant').hide();

        if ($(this).val() === 'linear-gradient') {
            gradientWrap.find('.wa-gradient-color-rotation').show();

        } else if ($(this).val() === 'radial-gradient') {
            gradientWrap.find('.wa-gradient-color-shape').show();
        }

        waUpdateGradient(gradientWrap);
    });

    waInitGradient();

    const wpColorPicker = $('.wa-wp-color-picker,.wa-color-palette').not('.wa-gradient-select-color').find('input[type="text"]');

    if (wpColorPicker.length) {
        wpColorPicker.wpColorPicker(wpColorPickerOptions);

        setTimeout(function () {
            $('.wa-color-palette[data-removable="1"]').each(function () {
                let waPickerContainer = $(this).find('.wp-picker-container');

                if (waPickerContainer.length > 0) {
                    waPickerContainer.append('<button type="button" class="wa-remove-color"><i class="wa-icon-cross"></i></button>');
                }
            });

            waColorPaletteInit();
        }, 500);
    }

    $('.wa-color-palette .wa-add-color').unbind("click").on('click', function (e) {
        e.preventDefault();

        let $this = $(this),
            waColorPalette = $this.closest('.wa-color-palette'),
            waColorPaletteItems = waColorPalette.find('.wa-color-palette-items'),
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
            waColorPalette.find('.wa-remove-color').show();
            waColorInputClone.closest('.wp-picker-container').append('<button type="button" class="wa-remove-color"><i class="wa-icon-cross"></i></button>');
        }

        waColorPaletteInit();
        waActiveSettingsForm();
    });

    $('.wa-add-repeatable').unbind("click").on('click', function (e) {
        e.preventDefault();

        let $this = $(this), repeatablePosition = $this.data('position'),
            waRepeatable = $this.closest('.wa-repeatable'), repeatableMax = waRepeatable.data('max-repeat'),
            waRepeatableFieldsWrap = waRepeatable.find('.wa-repeatable-fields-wrap'),
            waRepeatableFirstFieldsWrap = waRepeatable.find('.wa-repeatable-fields-wrap').first(),
            waRepeatableLastFieldsWrap = waRepeatable.find('.wa-repeatable-fields-wrap').last(),
            waRepeatableCloneFieldsWrap;

        if (repeatableMax !== undefined && waRepeatableFieldsWrap.length >= parseInt(repeatableMax)) {
            return;
        }

        if (repeatableMax !== undefined && waRepeatableFieldsWrap.length + 1 >= parseInt(repeatableMax)) {
            waRepeatable.find('.wa-add-repeatable').attr('disable', 'true');
        }

        if (waRepeatableFieldsWrap.length >= 1) {
            waRepeatable.find('.wa-remove-repeatable').show();
            waRepeatable.find('.wa-move-up-repeatable').show();
            waRepeatable.find('.wa-move-down-repeatable').show();
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
        $('.wa-color-palette .wa-remove-color').unbind("click").on('click', function (e) {
            let $this = $(this),
                waColorPalette = $this.closest('.wa-color-palette');

            $this.closest('.wp-picker-container').slideUp("normal", function () {
                $(this).remove();

                waColorPalette.find('.wa-add-color').removeAttr('disable');

                if (waColorPalette.find('.wp-picker-container').length <= 1) {
                    waColorPalette.find('.wa-remove-color').hide();
                }

                waActiveSettingsForm();
            });
        });
    }

    function waRepeatableInit() {
        $('.wa-remove-repeatable').unbind("click").on('click', function (e) {
            e.preventDefault();
            let $this = $(this), waRepeatable = $this.closest('.wa-repeatable');

            //$this.closest('.wa-repeatable-fields-wrap').remove();
            $this.closest('.wa-repeatable-fields-wrap').slideUp("normal", function () {
                $(this).remove();

                waRepeatable.find('.wa-add-repeatable').removeAttr('disable');

                if (waRepeatable.find('.wa-repeatable-fields-wrap').length <= 1) {
                    waRepeatable.find('.wa-remove-repeatable').hide();
                    waRepeatable.find('.wa-move-up-repeatable').hide();
                    waRepeatable.find('.wa-move-down-repeatable').hide();
                }

                waActiveSettingsForm();
            });
        });

        $('.wa-move-up-repeatable').unbind("click").on('click', function (e) {
            e.preventDefault();

            let $this = $(this), waRepeatableFieldsWrap = $this.closest('.wa-repeatable-fields-wrap'),
                waPrevRepeatableFieldsWrap = waRepeatableFieldsWrap.prev('.wa-repeatable-fields-wrap');

            let copyTo = waPrevRepeatableFieldsWrap.clone(true), copyFrom = waRepeatableFieldsWrap.clone(true);
            waPrevRepeatableFieldsWrap.replaceWith(copyFrom);
            waRepeatableFieldsWrap.replaceWith(copyTo);

            waActiveSettingsForm();
        });

        $('.wa-move-down-repeatable').unbind("click").on('click', function (e) {
            e.preventDefault();

            let $this = $(this), waRepeatableFieldsWrap = $this.closest('.wa-repeatable-fields-wrap'),
                waNextRepeatableFieldsWrap = waRepeatableFieldsWrap.next('.wa-repeatable-fields-wrap');

            let copyTo = waNextRepeatableFieldsWrap.clone(true), copyFrom = waRepeatableFieldsWrap.clone(true);
            waNextRepeatableFieldsWrap.replaceWith(copyFrom);
            waRepeatableFieldsWrap.replaceWith(copyTo);

            waActiveSettingsForm();
        });
    }

    setTimeout(function () {
        $('.wa-repeatable').each(function () {
            let waRepeatableFieldsWrap = $(this).find('.wa-repeatable-fields-wrap');

            if (waRepeatableFieldsWrap !== undefined && waRepeatableFieldsWrap.length > 1) {
                $(this).find('.wa-remove-repeatable').show();
                $(this).find('.wa-move-up-repeatable').show();
                $(this).find('.wa-move-down-repeatable').show();
            }
        });

        waRepeatableInit();
    }, 500);
});