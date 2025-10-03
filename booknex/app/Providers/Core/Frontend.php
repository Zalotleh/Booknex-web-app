<?php

namespace BookneticApp\Providers\Core;

use BookneticApp\Backend\Settings\Helpers\LocalizationService;
use BookneticApp\Frontend\Controller\ForgotPasswordAjax;
use BookneticApp\Frontend\Controller\SigninAjax;
use BookneticApp\Frontend\Controller\SignupAjax;
use BookneticApp\Integrations\LoginButtons\FacebookLogin;
use BookneticApp\Integrations\LoginButtons\GoogleLogin;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\WpShortcodes\Concrete\BookingPopupShortcode;
use BookneticApp\Providers\WpShortcodes\Concrete\BookneticShortcode;
use BookneticApp\Providers\WpShortcodes\Concrete\ChangeStatusShortcode;
use BookneticApp\Providers\WpShortcodes\Concrete\ForgotPasswordShortcode;
use BookneticApp\Providers\WpShortcodes\Concrete\SignInShortcode;
use BookneticApp\Providers\WpShortcodes\Concrete\SignUpShortcode;
use BookneticApp\Providers\WpShortcodes\WpShortcodeRegistry;

class Frontend
{
    const VIEW_DIR = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Frontend' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;

    public static function init()
    {
        do_action('bkntc_frontend');

        self::checkSocialLogin();

        LocalizationService::changeLanguageIfNeed();

        self::initAjaxRequests();
        self::initAjaxRequests(SigninAjax::class);
        self::initAjaxRequests(SignupAjax::class);
        self::initAjaxRequests(ForgotPasswordAjax::class);

        BookingPanelService::loadBookingPanelAssets();

        self::registerWpShortcodes();
    }

    private static function registerWpShortcodes()
    {
        WpShortcodeRegistry::register('booknetic', BookneticShortcode::instance());
        WpShortcodeRegistry::register('booknetic-booking-button', BookingPopupShortcode::instance());
        WpShortcodeRegistry::register('booknetic-change-status', ChangeStatusShortcode::instance());

        WpShortcodeRegistry::register('booknetic-signin', SignInShortcode::instance());
        WpShortcodeRegistry::register('booknetic-signup', SignUpShortcode::instance());
        WpShortcodeRegistry::register('booknetic-forgot-password', ForgotPasswordShortcode::instance());
    }

    private static function checkSocialLogin()
    {
        $booknetic_action = Helper::_get(Helper::getSlugName() . '_action', '', 'string');
        if ($booknetic_action == 'facebook_login') {
            Helper::redirect(FacebookLogin::getLoginURL());
        } else if ($booknetic_action == 'facebook_login_callback') {
            $data = FacebookLogin::getUserData();
            echo bkntc__('Loading...');
            echo '<script>var booknetic_user_data = ' . json_encode($data) . ';</script>';
            exit;
        } else if ($booknetic_action == 'google_login') {
            Helper::redirect(GoogleLogin::getLoginURL());
        } else if ($booknetic_action == 'google_login_callback') {
            $data = GoogleLogin::getUserData();
            echo bkntc__('Loading...');
            echo '<script>var booknetic_user_data = ' . json_encode($data) . ';</script>';
            exit;
        }

    }

    public static function initAjaxRequests($class = false)
    {
        $controllerClass = $class !== false ? $class : \BookneticApp\Frontend\Controller\Ajax::class;
        $methods = get_class_methods($controllerClass);
        $actionPrefix = (is_user_logged_in() ? 'wp_ajax_' : 'wp_ajax_nopriv_') . 'bkntc_';
        $controllerClass = new $controllerClass();

        foreach ($methods as $method) {
            // break helper methods
            if (strpos($method, '_') === 0)
                continue;

            add_action($actionPrefix . $method, function () use ($controllerClass, $method) {
                do_action("bkntc_before_frontend_request_" . $method);

                $result = call_user_func([$controllerClass, $method]);

                $result = apply_filters('bkntc_after_frontend_request_' . $method, $result);

                if (is_array($result)) {
                    echo json_encode($result);
                } else {
                    echo $result;
                }

                exit();
            });
        }
    }
}