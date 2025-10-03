(($) => {
    'use strict';

    $(document).ready(() => {
        $('#booknetic_settings_area')
            .on('click', '.settings-save-btn', () => {
                const data = new FormData();

                data.append('currentPassword', $('#currentPassword').val())
                data.append('newPassword', $('#newPassword').val())
                data.append('newPasswordConfirm', $('#newPasswordConfirm').val())

                booknetic.ajax('save_change_password', data, () => {
                    booknetic.toast(booknetic.__('saved_successfully'), 'success');
                });
            });
    });
})(jQuery);