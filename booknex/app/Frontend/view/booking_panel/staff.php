<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var mixed $parameters
 */

if ( count( $parameters[ 'staff' ] ) == 0 ): ?>
    <div class="booknetic_empty_box">
        <img class="booknetic_card_staff_image"
             src="<?php echo Helper::assets( 'images/empty-staff.svg', 'front-end' ) ?>" alt="empty-staff">
        <span><?php echo bkntc__( 'Staff not found. Please go back and select a different option.' ) ?>
    </div>
    <?php return; ?>
<?php endif; ?>

<?php $footerTextOption = Helper::getOption( 'footer_text_staff', '1' ); ?>

<div class="booknetic_card_container">
    <?php if ( Helper::getOption( 'any_staff', 'off' ) == 'on' ) : ?>
        <div class="booknetic_card booknetic_fade" data-id="-1">
            <div class="booknetic_card_image">
                <img class="booknetic_card_staff_image" src="<?php echo Helper::icon( 'any_staff.svg', 'front-end' ) ?>"
                     alt="">
            </div>
            <div class="booknetic_card_title">
                <div class="booknetic_card_title_first"><?php echo bkntc__( 'Any staff' ) ?></div>
                <?php if ( $footerTextOption != '4' ) : ?>
                    <div class="booknetic_card_description"><?php echo bkntc__( 'Select an available staff' ) ?></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php $staffList = apply_filters( 'bkntc_booking_panel_render_staff_info', $parameters[ 'staff' ] ); ?>

    <?php foreach ( $staffList as $staff ) : ?>
        <div class="booknetic_card booknetic_fade" data-id="<?php echo $staff[ 'id' ] ?>">
            <div class="booknetic_card_image">
                <img class="booknetic_card_staff_image" alt="staff-image"
                     src="<?php echo Helper::profileImage( $staff[ 'profile_image' ], 'Staff' ) ?>">
            </div>
            <div class="booknetic_card_title">
                <div class="booknetic_card_title_first"><?php echo $staff[ 'name' ] ?></div>
                <div class="booknetic_card_description">
                    <?php if ( ! empty( $staff[ 'profession' ] ) ) : ?>
                        <div class="booknetic_staff_profession"><?php echo $staff[ 'profession' ] ?></div>
                    <?php endif; ?>

                    <?php if ( $footerTextOption == '1' || $footerTextOption == '2' ) : ?>
                        <div><?php echo $staff[ 'email' ] ?></div>
                    <?php endif; ?>

                    <?php if ( $footerTextOption == '1' || $footerTextOption == '3' ) : ?>
                        <div><?php echo $staff[ 'phone_number' ] ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

</div>