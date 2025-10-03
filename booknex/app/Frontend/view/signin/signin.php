<?php

use BookneticApp\Providers\Helpers\Helper;

defined( 'ABSPATH' ) or die();

?>

<div class="booknetic_login">
    <div class="login_step">
        <div class="booknetic_header"><?php echo bkntc__('Sign In')?></div>
        <form class="booknetic_form">
            <div class="booknetic_form_element">
                <label for="booknetic_email"><?php echo bkntc__('Username or Email Address')?></label>
                <input type="text" id="booknetic_email" name="email">
            </div>
            <div class="booknetic_form_element">
                <label for="booknetic_password"><?php echo bkntc__('Password')?></label>
                <input type="password" id="booknetic_password" name="password">
            </div>
            <div>
                <div class="booknetic_form_element"><a href="<?php echo get_permalink( Helper::getOption('regular_forgot_password_page') )?>" class="booknetic_forgot_password"><img src="<?php echo Helper::icon('question.svg', 'front-end')?>" alt="?"><span><?php echo bkntc__('Forgot password?')?></span></a></div>
                <button type="submit" class="booknetic_btn_primary booknetic_login_btn"><?php echo bkntc__('SIGN IN')?></button>
            </div>
        </form>
        <div class="booknetic_footer">
            <span><?php echo bkntc__('Don\'t have an account?')?></span>
            <a href="<?php echo get_permalink( Helper::getOption('regular_sign_up_page') )?>"><?php echo bkntc__('Sign up')?></a>
        </div>
    </div>
    <div class="account_not_activated_step">
        <div class="booknetic_header"><?php echo bkntc__('Oh, your account is not activated! 😓')?></div>
        <div class="booknetic_check_your_email">
			<?php echo bkntc__("We need to verify your email before logging in.")?><br/>
			<?php echo bkntc__('Please, check your inbox for a confirmation link.')?>
        </div>
        <div class="booknetic_email_success">
            <img src="<?php echo Helper::assets('images/account-not-activated.svg', 'front-end')?>" alt="">
        </div>
        <div class="booknetic_footer booknetic_resend_activation">
            <span><?php echo bkntc__('Didn\'t receive the email?')?></span>
            <a href="javascript:;" class="booknetic_resend_activation_link"><?php echo bkntc__('Resend again')?></a>
        </div>
    </div>
</div>
