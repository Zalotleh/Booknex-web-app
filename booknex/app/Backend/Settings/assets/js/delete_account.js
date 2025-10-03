(($) => {
    'use strict';

    $(document).ready(() => {
        $('#booknetic_settings_area')
            .on('click', '#deleteAccount', () => {
                booknetic.confirm(booknetic.__('are_you_sure_want_to_delete'), 'danger', 'trash', () => {
                    booknetic.ajax('delete_tenant_account', {}, () => {
                        booknetic.toast(booknetic.__('Deleted'), 'success');
                        window.location.reload();
                    });
                });
            });
    });
})(jQuery);