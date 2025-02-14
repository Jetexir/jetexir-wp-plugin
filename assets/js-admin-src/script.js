jQuery(document).ready(function ($) {
    const settingsForm = document.getElementById('wa-settings-form'),
        settingsFooter = document.getElementById('wa-settings-footer'),
        settingsResetButton = document.getElementById("wa-settings-reset-button");
    let settingsSubmitActive = false;

    if (settingsForm) {
        if (settingsFooter)
            settingsFooter.classList.add('wa-submit-inactive');

        settingsForm.addEventListener('change', function () {
            if (settingsSubmitActive) return;
            settingsSubmitActive = true;

            if (settingsFooter)
                settingsFooter.classList.remove('wa-submit-inactive');
        });

        if (settingsResetButton) {
            settingsResetButton.addEventListener("click", () => {
                settingsSubmitActive = false;

                if (settingsFooter)
                    settingsFooter.classList.add('wa-submit-inactive');
            });
        }
    }
});