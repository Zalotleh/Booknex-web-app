(function ($) {
    "use strict";

    const $document = $(document);

    $document.ready(function () {
        booknetic.initMultilangInput($('#input_company_name'), 'options', 'company_name');
        booknetic.initMultilangInput($('#input_company_address'), 'options', 'company_address');

        const $bookneticSettings = $('#booknetic_settings_area');
        const $companyLogoUploadButton = $bookneticSettings.find(".company-logo-upload-btn");
        const $companyLogoRemoveButton = $bookneticSettings.find(".company-logo-remove-btn");
        const $companyLogoUploadInput = $bookneticSettings.find("#company_image_input");
        const $saveCompanyDetailsButton = $bookneticSettings.find(".settings-save-btn");
        const $companyImage = $bookneticSettings.find("#company_image_img");

        $companyLogoUploadInput.on('change', function () {
            const file = $(this)[0].files[0];

            if (!file) return;

            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];

            if (!allowedTypes.includes(file.type)) {
                booknetic.toast(booknetic.__('not_allowed_image_type'), 'unsuccess');
                return;
            }

            const reader = new FileReader();

            reader.onload = function (e) {
                $companyImage.attr('src', e.target.result);
                $companyLogoRemoveButton.prop('disabled', false);
            };

            reader.readAsDataURL(file);

            const formData = new FormData();
            formData.append('company_image', file);

            booknetic.ajax('handle_company_logo_upload', formData, function () {
                booknetic.toast(booknetic.__('saved_successfully'), 'success');
            });
        });

        $companyLogoUploadButton.on('click', () => $companyLogoUploadInput.click());

        $companyLogoRemoveButton.on('click', function () {
            booknetic.ajax('handle_company_logo_delete', {}, function () {
                booknetic.toast(booknetic.__('saved_successfully'), 'success');

                $companyImage.attr("src", defaultNoImage);
                $companyLogoUploadInput.val("");
                $companyLogoRemoveButton.prop('disabled', true);
            });
        });

        $saveCompanyDetailsButton.on('click', function () {
            const companyName = $("#input_company_name").val(),
                companyAddress = $("#input_company_address").val(),
                companyPhone = $("#input_company_phone").val(),
                companyWebsite = $("#input_company_website").val(),
                displayLogoOnBookingPanel = $("#input_display_logo_on_booking_panel").is(':checked') ? 'on' : 'off';

            const formData = new FormData();

            formData.append('company_name', companyName);
            formData.append('company_address', companyAddress);
            formData.append('company_phone', companyPhone);
            formData.append('company_website', companyWebsite);
            formData.append('display_logo_on_booking_panel', displayLogoOnBookingPanel);
            formData.append('translations', booknetic.getTranslationData($('#booknetic_settings_area')));

            booknetic.ajax('save_company_settings', formData, () => booknetic.toast(booknetic.__('saved_successfully'), 'success'));
        });

    });

})(jQuery);