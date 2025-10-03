<?php

namespace BookneticApp\Providers\WpShortcodes\Concrete;

use BookneticApp\Models\Customer;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\WpShortcodes\WpShortcode;

class ForgotPasswordShortcode extends WpShortcode
{
    public function index($attrs): string
    {
        wp_enqueue_script('booknetic-forgot-password', Helper::assets('js/booknetic-forgot-password.js', 'front-end'), ['jquery']);

        if (Permission::userId() > 0 && !$this->isPreview()) {
            $redirectToUrl = Helper::getURLOfUsersDashboard();
            wp_add_inline_script('booknetic-forgot-password', 'location.href="' . $redirectToUrl . '";');

            return bkntc__('You are already signed in. Please wait, you are being redirected...');
        }

        $resetToken = Helper::_get('reset_token', '', 'string');

        if (empty($resetToken)) {
            wp_enqueue_style('Booknetic-font', '//fonts.googleapis.com/css?family=Poppins:200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap');
            wp_enqueue_style('booknetic-forgot-password', Helper::assets('css/booknetic-forgot-password.css', 'front-end'));

            wp_localize_script('booknetic-forgot-password', 'BookneticDataFP', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'assets_url' => Helper::assets('/', 'front-end'),
                'localization' => []
            ]);

            return $this->view('forgot_password' . DIRECTORY_SEPARATOR . 'forgot_password.php', $attrs);
        }

        return $this->resetToken($resetToken);
    }

    private function resetToken($resetToken)
    {
        $tokenParts = explode('.', $resetToken);

        if (count($tokenParts) !== 3) {
            wp_add_inline_script('booknetic-forgot-password', 'location.href="' . htmlspecialchars(site_url()) . '";');
            return bkntc__('Something went wrong. Redirecting...');
        }

        $header = json_decode(base64_decode($tokenParts[0]), true);
        $payload = json_decode(base64_decode($tokenParts[1]), true);

        if (is_array($header) &&
            is_array($payload) &&
            array_key_exists('id', $header) && is_numeric($header['id']) &&
            array_key_exists('expire', $header) && is_numeric($header['expire']) &&
            array_key_exists('email', $payload)) {
            $customerId = $header['id'];
            $expire = $header['expire'];
            $email = $payload['email'];
        } else {
            wp_add_inline_script('booknetic-forgot-password', 'location.href="' . htmlspecialchars(site_url()) . '";');
            return bkntc__('Something went wrong. Redirecting...');
        }

        $secret = Helper::getOption('purchase_code', '', false);
        $secret = hash_hmac('SHA256', $email, $secret, true);

        if (!Helper::validateToken($resetToken, $secret)) {
            wp_add_inline_script('booknetic-forgot-password', 'location.href="' . htmlspecialchars(site_url()) . '";');
            return bkntc__('Something went wrong. Redirecting...');
        }
        if (Customer::getData($customerId, 'pending_password_reset') != 1) {
            wp_add_inline_script('booknetic-forgot-password', 'location.href="' . htmlspecialchars(site_url()) . '";');
            return bkntc__('Redirecting...');
        }
        if ($expire < Date::epoch()) {
            wp_add_inline_script('booknetic-forgot-password', 'location.href="' . htmlspecialchars(site_url()) . '";');
            return bkntc__('Token expired. Redirecting...');
        }
        wp_enqueue_style('Booknetic-font', '//fonts.googleapis.com/css?family=Poppins:200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap');
        wp_enqueue_style('booknetic-forgot-password', Helper::assets('css/booknetic-forgot-password.css', 'front-end'));

        wp_localize_script('booknetic-forgot-password', 'BookneticDataFP', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'date_format' => Helper::getOption('date_format', 'Y-m-d'),
            'assets_url' => Helper::assets('/', 'front-end'),
            'localization' => []
        ]);

        return $this->view('forgot_password' . DIRECTORY_SEPARATOR . 'forgot_password_complete.php', ['reset_token' => $resetToken]);
    }
}