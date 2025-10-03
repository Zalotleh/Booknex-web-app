<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;

?>
<div id="booknetic_settings_area">
    <link rel="stylesheet" href="<?php echo Helper::assets( 'css/delete_account.css', 'Settings' ) ?>">
    <script type="application/javascript"
            src="<?php echo Helper::assets( 'js/delete_account.js', 'Settings' ) ?>"></script>
    <div class="settings-light-portlet">
        <div class="ms-title"><?php echo bkntc__( 'Delete Account' ) ?></div>
        <div class="ms-content">
            <div class="delete-account-content">
                <p class="delete-account-warning"><?php echo bkntc__('Be Careful. Account deletion cannot be undone.') ?></p>
                <button class="delete-account-button" id="deleteAccount"><?php echo bkntc__('Delete account') ?></button>
            </div>
        </div>
    </div>
</div>