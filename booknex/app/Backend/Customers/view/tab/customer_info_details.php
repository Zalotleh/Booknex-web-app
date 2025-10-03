<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var mixed $parameters
 * @var mixed $customer
 */

$customer = $parameters['customer'];
?>
<div class="modal_payment">
    <div class="modal_payment-header d-flex justify-content-between align-items-center pb-4">
        <div class="modal_payment-profile d-flex align-items-center">
            <img src="<?php echo Helper::profileImage($customer['profile_image'], 'Customers'); ?>" alt="">
            <span><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></span>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-lg-6">
            <div class="modal_payment-card">
                <h6><?php echo bkntc__('Email') ?></h6>
                <span class="text-break"><?php echo $customer['email'] ?? bkntc__('N/A' ); ?></span>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="modal_payment-card">
                <h6><?php echo bkntc__('Phone') ?></h6>
                <span><?php echo $customer['phone_number'] ?? bkntc__('N/A' ); ?></span>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="modal_payment-card">
                <h6><?php echo bkntc__('Gender') ?></h6>
                <span><?php echo isset( $customer[ 'gender' ] ) ? bkntc__( ucfirst( $customer['gender'] ) ) : bkntc__('N/A' ); ?></span>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="modal_payment-card">
                <h6><?php echo bkntc__('Birthdate') ?></h6>
                <span><?php echo $customer['birthdate'] ?? bkntc__('N/A' ); ?></span>
            </div>
        </div>

        <div class="col-lg-12 mt-3">
            <div class="modal_payment-card">
                <h6><?php echo bkntc__('Note') ?></h6>
                <span><?php echo htmlspecialchars( $customer['notes'] ) ?? bkntc__('N/A' ); ?></span>
            </div>
        </div>
    </div>
</div>