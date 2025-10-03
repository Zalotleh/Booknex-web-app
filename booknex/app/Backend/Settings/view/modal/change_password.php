<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;

?>
<div id="booknetic_settings_area">
    <script type="application/javascript"
            src="<?php echo Helper::assets( 'js/change_password.js', 'Settings' ) ?>"></script>

    <div class="actions_panel clearfix">
        <button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i
                    class="fa fa-check pr-2"></i> <?php echo bkntc__( 'SAVE CHANGES' ) ?></button>
    </div>

    <div class="settings-light-portlet">
        <div class="ms-title"><?php echo bkntc__( 'Change password' ) ?></div>
        <div class="ms-content">
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="currentPassword"><?php echo bkntc__( 'Current Password' ) ?>:</label>
                    <input type="password" class="form-control" data-multilang="true" id="currentPassword" placeholder="**************"/>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="newPassword"><?php echo bkntc__( 'New Password' ) ?>:</label>
                    <input type="password" class="form-control" data-multilang="true" id="newPassword" placeholder="**************"/>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="newPasswordConfirm"><?php echo bkntc__( 'New Password Again' ) ?>:</label>
                    <input type="password" class="form-control" data-multilang="true" id="newPasswordConfirm" placeholder="**************"/>
                </div>
            </div>
        </div>
    </div>
</div>