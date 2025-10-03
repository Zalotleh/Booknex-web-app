<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var int $_mn
 * @var array $parameters
 */
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/change_status.css', 'Appointments')?>">
<script type="text/javascript" src="<?php echo Helper::assets('js/change_status.js', 'Appointments')?>" id="change_status_JS" data-mn="<?php echo $_mn; ?>" data-appointment-ids="<?php echo implode(',', $parameters['ids']); ?>"></script>

<div class="fs-modal-title">
    <div class="title-icon badge-lg badge-purple"><i class="fa fa-pencil-alt"></i></div>
	<div class="title-text"><?php echo bkntc__('Change status')?></div>
	<div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
	<div class="fs-modal-body-inner">
        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="input_appointment_status"><?php echo bkntc__('New status')?> <span class="required-star">*</span></label>

                <span class="form-control-plaintext appointment-status-btn">
                    <button class="btn btn-lg btn-outline-secondary"  type="button" data-status="<?php echo esc_html($parameters['selected_status']['slug'])?>" data-toggle="dropdown"><i class="<?php echo $parameters['selected_status']['icon']?>" style="color:<?php echo $parameters['selected_status']['color']?>"></i> <span class="c_status"><?php echo esc_html($parameters['selected_status']['title'])?></span> <img src="<?php echo Helper::icon('arrow-down-xs.svg')?>"></button>
                    <div class="dropdown-menu appointment-status-panel">
                        <?php
                        foreach ( $parameters['statuses'] AS $stName => $status )
                        {
                            echo '<a class="dropdown-item" data-status="' . $stName . '"><i class="' . $status['icon'] . '" style="color: ' . $status['color'] . ';"></i> ' . $status['title'] . '</a>';
                        }
                        ?>
                    </div>
                </span>
            </div>
        </div>
	</div>
</div>

<div class="fs-modal-footer">
    <div class="footer_left_action">
        <input type="checkbox" id="input_run_workflows" checked>
        <label for="input_run_workflows" class="font-size-14 text-secondary"><?php echo bkntc__('Run workflows on save')?></label>
    </div>

	<button type="button" class="btn btn-lg btn-default" data-dismiss="modal"><?php echo bkntc__('CLOSE')?></button>
    <button type="button" class="btn btn-lg btn-primary" id="changeAppointmentStatusBtn"><?php echo bkntc__('SAVE')?></button>
</div>
