<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var mixed $parameters
 */

$customerIdentifier = $parameters['customer_identifier'];
$hideIdentifier = empty( $parameters['email'] ) || ! $parameters['can_change_email_input'];
$customerId = (int)$parameters['customer_id'];

$hideNameInput = $parameters['hide_name_input'];
$hideSurnameInput = $parameters['hide_surname_input'];
$hideEmailInput = $parameters['hide_email_input'];
$hidePhoneInput = $parameters['hide_phone_input'];

$identifier = $parameters['customer_identifier'];
?>

<div>
	<?php if( $parameters['show_facebook_login_button'] ): ?>
	    <button type="button" class="booknetic_social_login_facebook" data-href="<?php echo $parameters['facebook_login_button_url']?>"><?php echo bkntc__('CONTINUE WITH FACEBOOK')?></button>
	<?php endif; ?>

    <?php if( $parameters['show_google_login_button'] ): ?>
        <button type="button" class="booknetic_social_login_google" data-href="<?php echo $parameters['google_login_button_url']?>"><?php echo bkntc__('CONTINUE WITH GOOGLE')?></button>
	<?php endif; ?>
</div>

<div class="form-row">
    <div class="form-group col-md-6" data-bkntc-customer-info="true" <?php echo $hideEmailInput ? 'style="display: none;"' : ''?>>
        <label for="bkntc_input_email" <?php echo $parameters['email_is_required']=='on'?' data-required="true"':''?>><?php echo bkntc__('Email')?></label>
        <input type="text" id="bkntc_input_email" class="form-control<?php echo $identifier == 'email' ? ' bkntc_input_identifier_input' : '' ?>" name="email" value="<?php echo htmlspecialchars($parameters['email'])?>" <?php echo $parameters['disable_email_input'] ? "disabled" : "" ?> <?php echo $identifier == 'email' && $customerId > 0 ? ' data-customer-id="'.$customerId.'"' : '' ?> />
        <?php if( $identifier == 'email' ):?>
        <img class="bkntc_input_identifier_clear" src="<?php print Helper::assets('icons/close.svg', 'front-end')?>" <?php echo $hideIdentifier ? 'style="display:none;"' : ''?>>
        <img class="bkntc_input_identifier_loading" src="<?php print Helper::assets('icons/loading.svg', 'front-end')?>" style="display:none;">
        <?php endif;?>
    </div>
    <div class="form-group col-md-6" data-bkntc-customer-info="true" <?php echo $hidePhoneInput ? 'style="display: none;"' : ''?>>
        <label for="bkntc_input_phone" class="bkntc_input_phone__label" <?php echo $parameters['phone_is_required']=='on'?' data-required="true"':''?>><?php echo bkntc__('Phone')?></label>
        <div  class="bkntc_input_phone-container">
            <input type="tel" id="bkntc_input_phone" class="form-control<?php echo $identifier == 'phone' ? ' bkntc_input_identifier_input' : '' ?>" name="phone" value="<?php echo htmlspecialchars($parameters['phone'])?>" data-country-code="<?php echo $parameters['default_phone_country_code']?>"<?php print $parameters['disable_phone_input']?' disabled':''?> <?php echo $identifier == 'phone' && $customerId > 0 ? ' data-customer-id="'.$customerId.'"' : '' ?> />
            <?php if( $identifier == 'phone' ):?>
                <img class="bkntc_input_identifier_clear" src="<?php print Helper::assets('icons/close.svg', 'front-end')?>" <?php echo $hideIdentifier ? 'style="display:none;"' : ''?>>
                <img class="bkntc_input_identifier_loading" src="<?php print Helper::assets('icons/loading.svg', 'front-end')?>" style="display:none;">
            <?php endif;?>
        </div>
    </div>
</div>

<div class="form-row">
	<div class="form-group col-md-<?php echo $parameters['show_only_name'] ? '12' : '6'?>" data-bkntc-customer-info="true" <?php echo $hideNameInput ? 'style="display: none;"' : ''?>>
		<label for="bkntc_input_name" data-required="true"><?php echo $parameters[ 'show_only_name' ] ? bkntc__( 'Full Name' ) : bkntc__('Name') ?></label>
		<input type="text" id="bkntc_input_name" class="form-control" name="first_name" value="<?php echo htmlspecialchars($parameters['name'] . ( $parameters['show_only_name'] ? ($parameters['name'] ? ' ' : '') . $parameters['surname'] : '' ))?>"<?php print $parameters['disable_name_input']?' disabled':''?>>
	</div>
	<div class="form-group col-md-6<?php echo $parameters['show_only_name'] ? ' booknetic_hidden bkntc_hidden_lastname' : ''?>" data-bkntc-customer-info="true" <?php echo $hideSurnameInput ? 'style="display: none;"' : ''?>>
		<label for="bkntc_input_surname"<?php echo $parameters['show_only_name'] ? '' : ' data-required="true"'?>><?php echo bkntc__('Surname')?></label>
		<input type="text" id="bkntc_input_surname" class="form-control" name="last_name" value="<?php echo htmlspecialchars($parameters['show_only_name'] ? '' : $parameters['surname'])?>"<?php print $parameters['disable_surname_input']?' disabled':''?>>
	</div>
</div>

<div class="form-row bkntc-information-step-info-container <?php echo $customerId > 0 ? '' : 'booknetic_hidden'?>" data-bkntc-customer-id="true">
    <div class="form-group col-md-12">
        <label><img src="<?php echo Helper::assets('icons/information-logo.svg', 'front-end')?>" alt=""><span><?php echo bkntc__('Information')?></span></label>
        <div><?php echo bkntc__("We've found your account in our system and have automatically filled in your details.")?></div>
    </div>
</div>

<?php if( $parameters['how_many_people_can_bring'] > 0 ) : ?>
    <div id="booknetic_bring_someone_section">
        <div class="form-row">
            <div class="form-group col-md-6">
                <input type="checkbox" id="booknetic_bring_someone_checkbox">
                <label for="booknetic_bring_someone_checkbox"><?php echo bkntc__('Bring People with You')?></label>
            </div>

            <div class="form-group col-md-6 booknetic_number_of_brought_customers d-none">
                <label for=""><?php echo bkntc__('Number of people:') ?></label>
                <div class="booknetic_number_of_brought_customers_quantity">
                    <div class="booknetic_number_of_brought_customers_dec">-</div>
                    <input type="text" class="booknetic_number_of_brought_customers_quantity_input" value="0" data-max-quantity="<?php echo ($parameters['how_many_people_can_bring']);?>">
                    <div class="booknetic_number_of_brought_customers_inc">+</div>
                </div>
            </div>
        </div>
    </div>
<?php endif;?>

<?php do_action( 'bkntc_after_information_inputs', $parameters['service']); ?>
