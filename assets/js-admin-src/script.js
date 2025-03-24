jQuery(document).ready(function ($) {
    var wpColorPickerOptions = {
        defaultColor: false, change: function (event, ui) {
            waActiveSettingsForm();
        }, clear: function () {
            waActiveSettingsForm();
        }, hide: true, palettes: true
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

    const wpColorPicker = $('.wa-wp-color-picker input[type="text"], .wa-color-palette input[type="text"]');

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