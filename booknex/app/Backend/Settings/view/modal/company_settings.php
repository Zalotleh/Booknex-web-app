<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var array $parameters
 */

$defaultNoImage = $parameters['defaultNoImage'];
?>

<div id="booknetic_settings_area">
    <script>
        var defaultNoImage = '<?php echo $defaultNoImage;?>';
    </script>
	<link rel="stylesheet" href="<?php echo Helper::assets('css/company_settings.css', 'Settings')?>">
	<script type="application/javascript" src="<?php echo Helper::assets('js/company_settings.js', 'Settings')?>"></script>

	<div class="actions_panel clearfix">
		<button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php echo bkntc__('SAVE CHANGES')?></button>
	</div>

	<div class="settings-light-portlet">
		<div class="ms-title"><?php echo bkntc__('Company details')?></div>
		<div class="ms-content">

			<div class="form-row">
				<div class="form-group col-md-12">
                    <div class="d-flex align-items-center company-logo-container gap">
                        <div class="selected-company-image">
                            <img src="<?php echo Helper::getOption('company_image', '') ? Helper::profileImage(Helper::getOption('company_image', ''), 'Settings') : $defaultNoImage; ?>" id="company_image_img" class="rounded-circle" alt="<?php echo bkntc__('Company logo')?>">
                        </div>
                        <div>
                            <h2 class="company-logo-heading p-0 m-0"><?php echo bkntc__('Company Logo')?></h2>
                            <div class="d-flex align-items-center gap">
                                <button class="btn btn-primary btn-sm company-logo-upload-btn"><?php echo bkntc__('Upload Image')?></button>
                                <button class="btn btn-sm btn-outline-secondary company-logo-remove-btn"
                                    <?php echo Helper::getOption('company_image', '') ? '' : 'disabled'; ?>>
                                    <?php echo bkntc__('Remove'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
					<input type="file" class="d-none" id="company_image_input">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="input_company_name"><?php echo bkntc__('Company name')?>:</label>
					<input class="form-control" data-multilang="true" id="input_company_name" value="<?php echo htmlspecialchars( Helper::getOption('company_name', '') )?>">
				</div>
				<div class="form-group col-md-6">
					<label for="input_company_address"><?php echo bkntc__('Address')?>:</label>
					<input class="form-control" data-multilang="true" id="input_company_address" value="<?php echo htmlspecialchars( Helper::getOption('company_address', '') )?>">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="input_company_phone"><?php echo bkntc__('Phone')?>:</label>
					<input class="form-control" id="input_company_phone" value="<?php echo htmlspecialchars( Helper::getOption('company_phone', '') )?>">
				</div>
				<div class="form-group col-md-6">
					<label for="input_company_website"><?php echo bkntc__('Website')?>:</label>
					<input class="form-control" id="input_company_website" value="<?php echo htmlspecialchars( Helper::getOption('company_website', '') )?>">
				</div>
			</div>

			<?php if( \BookneticApp\Providers\Core\Capabilities::tenantCan( 'upload_logo_to_booking_panel' ) ):?>
			<div class="form-row">
				<div class="form-group col-md-6">
					<div class="form-control-checkbox">
						<label for="input_display_logo_on_booking_panel"><?php echo bkntc__('Display a company logo on the  Booking panel')?>:</label>
						<div class="fs_onoffswitch">
							<input type="checkbox" class="fs_onoffswitch-checkbox" id="input_display_logo_on_booking_panel"<?php echo Helper::getOption('display_logo_on_booking_panel', 'off')=='on'?' checked':''?>>
							<label class="fs_onoffswitch-label" for="input_display_logo_on_booking_panel"></label>
						</div>
					</div>
				</div>
			</div>
			<?php endif;?>

		</div>
	</div>
</div>