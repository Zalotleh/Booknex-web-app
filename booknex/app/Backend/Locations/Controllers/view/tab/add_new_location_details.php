<?php

use BookneticApp\Backend\Locations\DTOs\Response\LocationResponse;

defined( 'ABSPATH' ) or die();

/**
 * @var LocationResponse $parameters
 */

?>
<div class="form-row">
    <div class="form-group col-md-12">
        <label for="input_location_name"><?php echo bkntc__( 'Location Name' ) ?> <span
                    class="required-star">*</span></label>
        <input type="text" data-multilang="true" data-multilang-fk="<?php echo $parameters->getId() ?>"
               class="form-control" id="input_location_name"
               value="<?php echo htmlspecialchars( $parameters->getName() ) ?>">
    </div>
</div>

<div class="form-group">
    <label for="input_image"><?php echo bkntc__( 'Image' ) ?></label>
    <input type="file" class="form-control" id="input_image">
    <div class="form-control"
         data-label="<?php echo bkntc__( 'BROWSE' ) ?>"><?php echo bkntc__( '(PNG, JPG, max 800x800 to 5mb)' ) ?></div>
</div>

<div class="form-row">
    <div class="form-group col-md-12">
        <label for="input_address"><?php echo bkntc__( 'Address' ) ?></label>
        <input type="text" class="form-control" data-multilang="true"
               data-multilang-fk="<?php echo $parameters->getId() ?>" id="input_address"
               value="<?php echo htmlspecialchars( $parameters->getAddress() ) ?>">
        <div id="divmap"></div>
        <div id="address_details"></div>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-12">
        <label for="input_phone"><?php echo bkntc__( 'Phone' ) ?></label>
        <input type="text" class="form-control" id="input_phone"
               value="<?php echo htmlspecialchars( $parameters->getPhoneNumber() ) ?>">
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-12">
        <label for="input_note"><?php echo bkntc__( 'Description' ) ?></label>
        <textarea id="input_note" data-multilang="true" data-multilang-fk="<?php echo $parameters->getId() ?>"
                  class="form-control"><?php echo htmlspecialchars( $parameters->getNotes() ) ?></textarea>
    </div>
</div>