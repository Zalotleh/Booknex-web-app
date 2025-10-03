<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Models\Service;

/**
 * @var array $parameters
 */

$filteredExtras = array_filter($parameters['extras'], fn($extra) => $extra['is_active'] == 1 && $extra['min_quantity'] > 0);
$extrasCount = count($filteredExtras)

?>

<div class="service-modal-container" data-service-extras-count='<?php echo $extrasCount ?>'>
	<div class="form-row">
		<div class="form-group col-md-12">
			<div class="form-control-checkbox">
				<label for="service_settings_custom_only_visible_to_staff"><?php echo bkntc__( 'Only visible to staff' ); ?>:</label>
				<div class="fs_onoffswitch">
					<input type="checkbox" class="fs_onoffswitch-checkbox" id="service_settings_custom_only_visible_to_staff" <?php echo $parameters[ 'only_visible_to_staff' ] ? 'checked' : ''; ?>>
					<label class="fs_onoffswitch-label" for="service_settings_custom_only_visible_to_staff"></label>
				</div>
			</div>
		</div>
	</div>

    <div class="form-row">
        <div class="form-group col-md-12">
            <div class="form-control-checkbox">
                <label for="service_settings_custom_payment_methods_enabled"><?php echo bkntc__( 'Set service specific payment methods' ); ?>:</label>
                <div class="fs_onoffswitch">
                    <input type="checkbox" class="fs_onoffswitch-checkbox" id="service_settings_custom_payment_methods_enabled" <?php echo $parameters[ 'custom_payment_methods_enabled' ] ? 'checked' : ''; ?>>
                    <label class="fs_onoffswitch-label" for="service_settings_custom_payment_methods_enabled"></label>
                </div>
            </div>
        </div>
    </div>

    <div id="serviceCustomPaymentMethodsContainer" class="form-row">
        <div class="form-group col-md-12">
            <label for="service_settings_custom_payment_methods">
                <?php echo bkntc__( 'Payment methods' ); ?>&nbsp;<span class="required-star">*</span>
            </label>
            <select id="service_settings_custom_payment_methods" class="form-control" multiple="multiple">
                <?php foreach ( PaymentGatewayService::getInstalledGatewayNames() as $paymentGateway ): ?>
                    <option value="<?php echo htmlspecialchars( PaymentGatewayService::find( $paymentGateway )->getSlug() ); ?>" <?php echo in_array( PaymentGatewayService::find( $paymentGateway )->getSlug(), $parameters[ 'custom_payment_methods' ] ) ? 'selected' : ''; ?>><?php echo htmlspecialchars( PaymentGatewayService::find( $paymentGateway )->getTitle() ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group col-md-12">
            <label for="input_min_time_req_prior_booking"><?php echo bkntc__('Minimum time requirement prior to booking')?>:</label>
            <select class="form-control" id="input_min_time_req_prior_booking">

                <?php
                    $minimumTimeRequiredPriorBooking        = Helper::getMinTimeRequiredPriorBooking( $parameters[ 'id' ] );
                    $defaultMinimumTimeRequiredPriorBooking = Helper::getMinTimeRequiredPriorBooking();
                ?>

                <option value="0" <?php echo $minimumTimeRequiredPriorBooking == '0' ? ' selected' :'' ?> ><?php echo bkntc__( 'Disabled' ); echo '0' == $defaultMinimumTimeRequiredPriorBooking ?  ' ( '. bkntc__( 'Default' ) . ' )' : ''; ?></option>
                <?php foreach ( Helper::timeslotsAsMinutes() as $minute ): ?>
                    <option value="<?php echo $minute ?>"<?php echo $minimumTimeRequiredPriorBooking == $minute ? ' selected':'' ?> ><?php echo Helper::secFormat($minute * 60 ); echo $minute == $defaultMinimumTimeRequiredPriorBooking ?  ' ( '. bkntc__( 'Default' ) . ' )' : ''; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-12">
            <div class="form-control-checkbox">
                <label for="enableLimitedBookingDays"><?php echo bkntc__( 'Limited Booking Days' ); ?>:</label>
                <div class="fs_onoffswitch">
                    <input type="checkbox" class="fs_onoffswitch-checkbox" id="enableLimitedBookingDays" <?php echo Service::getData( $parameters[ 'id' ], 'enable_limited_booking_days' ) ? 'checked' : ''; ?>>
                    <label class="fs_onoffswitch-label" for="enableLimitedBookingDays"></label>
                </div>
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-12">
            <label for="input_available_days_for_booking"></label>
            <input type="number" class="form-control" id="input_available_days_for_booking" min="0" value="<?php echo ( Service::getData( $parameters[ 'id' ], 'available_days_for_booking' ) ) ?? 365 ?>">
        </div>
    </div>
    <?php
        $serviceExtraLimitations = $parameters['service_extra_limitations'];

        $minLimitStatus = $parameters['service_extra_min_limit_enabled'];
        $minLimit = array_shift($serviceExtraLimitations);

        $maxLimitStatus = $parameters['service_extra_max_limit_enabled'];
        $maxLimit = array_shift($serviceExtraLimitations);
    ?>
    <!-- service extra limiter inputs -->
    <div class="form-row">
        <div class="form-group col-md-12">
            <div class="form-control-checkbox">
                <label for="service_extra_limiter_min"><?php echo bkntc__( 'Activate minimum service extras' ); ?>:</label>
                <div class="fs_onoffswitch">
                    <input type="checkbox" class="fs_onoffswitch-checkbox" id="service_extra_limiter_min" <?php echo $minLimitStatus ? 'checked' : ''; ?>>
                    <label class="fs_onoffswitch-label" for="service_extra_limiter_min"></label>
                </div>
            </div>
        </div>
    </div>
    <div class="form-row" id="service_extra_limiter_min_div" style="display: <?php echo $minLimitStatus ? 'block' : 'none'; ?>">
        <div class="form-group col-md-12">
            <input type="number" class="form-control" value="<?php echo $minLimitStatus ? $minLimit : '' ?>" id="service_extra_limiter_min_input" min="0" placeholder="<?php echo bkntc__('Enter minimum count for service extras')?>">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-12">
            <div class="form-control-checkbox">
                <label for="service_extra_limiter_max"><?php echo bkntc__( 'Activate maximum service extras' ); ?>:</label>
                <div class="fs_onoffswitch">
                    <input type="checkbox" class="fs_onoffswitch-checkbox" id="service_extra_limiter_max" <?php echo $maxLimitStatus ? 'checked' : ''; ?>>
                    <label class="fs_onoffswitch-label" for="service_extra_limiter_max"></label>
                </div>
            </div>
        </div>
    </div>
    <div class="form-row" id="service_extra_limiter_max_div" style="display: <?php echo $maxLimitStatus ? 'block' : 'none'; ?>">
        <div class="form-group col-md-12">
            <input type="number" class="form-control"  value="<?php echo $maxLimitStatus ? $maxLimit : '' ?>" placeholder="<?php echo bkntc__('Enter maximum count for service extras')?>" id="service_extra_limiter_max_input" min="0">
        </div>
    </div>
</div>


