(($) => {
    'use strict';

    $(document).ready(() => {
        $('#booknetic_settings_area')
            .on('click', '.settings-save-btn', () => {
                const data = new FormData();

                data.append('fullName', $('#tenantFullName').val())
                data.append('email', $('#tenantEmail').val())
                data.append('domain', $('#tenantDomain').val())

                booknetic.ajax('save_profile_details', data, () => {
                    booknetic.toast(booknetic.__('saved_successfully'), 'success');
                });
            })
            .on('click', '#uploadImage', () => {
                $('#imageInput').click();
            })
            .on('change', '#imageInput', (e) => {
                const data = new FormData();

                data.append('file', e.target.files[0])
                data.append('filter', 'image');

                booknetic.ajax('update_profile_picture', data, (response) => {
                    $('#profileImage').attr('src', response.url);
                    $('#removeImage').prop('disabled', false);
                    booknetic.toast(booknetic.__('saved_successfully'), 'success');
                })
            })
            .on('click', '#removeImage', () => {
                booknetic.ajax('delete_profile_picture', {}, (response) => {
                    $('#profileImage').attr('src', response.noPhotoUrl);
                    $('#removeImage').prop('disabled', true);
                    booknetic.toast(booknetic.__('saved_successfully'), 'success');
                });
            });
    });
})(jQuery);