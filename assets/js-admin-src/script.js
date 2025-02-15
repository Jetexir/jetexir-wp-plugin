jQuery(document).ready(function ($) {
    const settingsForm = document.getElementById('wa-settings-form'),
        settingsFooter = document.getElementById('wa-settings-footer'),
        settingsResetButton = document.getElementById("wa-settings-reset-button");
    let settingsSubmitActive = false;

    function activeSettingsForm() {
        if (settingsSubmitActive) return;
        settingsSubmitActive = true;

        if (settingsFooter)
            settingsFooter.classList.remove('wa-submit-inactive');
    }

    if (settingsForm) {
        if (settingsFooter)
            settingsFooter.classList.add('wa-submit-inactive');

        settingsForm.addEventListener('change', function () {
            activeSettingsForm();
        });

        if (settingsResetButton) {
            settingsResetButton.addEventListener("click", () => {
                settingsSubmitActive = false;

                if (settingsFooter)
                    settingsFooter.classList.add('wa-submit-inactive');
            });
        }
    }

    const wpColorPicker = $('.wa-wp-color-picker input[type="text"]');

    if (wpColorPicker.length) {
        var myOptions = {
            defaultColor: false,
            change: function (event, ui) {
                activeSettingsForm();
            },
            clear: function () {
                activeSettingsForm();
            },
            hide: true,
            palettes: true
        };

        wpColorPicker.wpColorPicker(myOptions);
    }
});