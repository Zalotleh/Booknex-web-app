<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var mixed $parameters
 */
?>

<?php if ( count( $parameters[ 'locations' ] ) == 0 ): ?>
    <div class="booknetic_empty_box">
        <img alt="" src="<?php echo Helper::assets( 'images/empty-service.svg', 'front-end' ) ?>">
        <span>
            <?php echo bkntc__( 'There is no any Location for select.' ) ?>
        </span>
    </div>
<?php else: ?>
    <div class="booknetic_card_container">
        <?php foreach ( $parameters[ 'locations' ] as $eq => $location ): ?>
            <div class="booknetic_card booknetic_fade" data-id="<?php echo $location[ 'id' ] ?>">
                <div class="booknetic_card_image">
                    <img class="booknetic_card_location_image"
                         src="<?php echo Helper::profileImage( $location[ 'image' ], 'Locations' ) ?>">
                </div>
                <div class="booknetic_card_title">
                    <div class="booknetic_card_title_first"><?php echo htmlspecialchars( $location[ 'name' ] ) ?></div>
                    <div class="booknetic_card_description<?php echo Helper::getOption( 'hide_address_of_location', 'off' ) == 'on' ? ' booknetic_hidden' : '' ?>"><?php echo htmlspecialchars( $location[ 'address' ] ) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
