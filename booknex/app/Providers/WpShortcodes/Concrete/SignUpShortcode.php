<?php

namespace BookneticApp\Providers\WpShortcodes\Concrete;

use BookneticApp\Models\Customer;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\WpShortcodes\WpShortcode;

class SignUpShortcode extends WpShortcode
{
    public function index($attrs): string
    {
        wp_enqueue_script('booknetic-signup', Helper::assets('js/booknetic-signup.js', 'front-end'), ['jquery']);

        if (Permission::userId() > 0 && !$this->isPreview()) {
            $redirectToUrl = Helper::getURLOfUsersDashboard();
            wp_add_inline_script('booknetic-signup', 'location.href="' . $redirectToUrl . '";');

            return bkntc__('You are already signed in. Please wait, you are being redirected...');
        }

        $customerPanelUrl = Helper::customerPanelURL();

        $activationToken = Helper::_get('activation_token', '', 'string');
        $redirectToUrl = Helper::_get('redirect_to', $_COOKIE['SigninRedirectURL'] ?? $customerPanelUrl ?: site_url(), 'string');

        if (!empty($activationToken)) {
            $tokenParts = explode('.', $activationToken);

            if (count($tokenParts) !== 3) {
                wp_add_inline_script('booknetic-signup', 'location.href="' . htmlspecialchars(site_url()) . '";');
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
                wp_add_inline_script('booknetic-signup', 'location.href="' . htmlspecialchars(site_url()) . '";');
                return bkntc__('Something went wrong. Redirecting...');
            }

            $secret = Helper::getOption('purchase_code', '', false);
            $secret = hash_hmac('SHA256', $email, $secret, true);

            if (!Helper::validateToken($activationToken, $secret)) {
                wp_add_inline_script('booknetic-signup', 'location.href="' . htmlspecialchars(site_url()) . '";');
                return bkntc__('Something went wrong. Redirecting...');
            }

            $customerInfo = Customer::get($customerId);

            if (!$customerInfo || Customer::getData($customerId, 'pending_activation') != 1) {
                wp_add_inline_script('booknetic-signup', 'location.href="' . urldecode(htmlspecialchars($redirectToUrl)) . '";');
                return bkntc__('Redirecting...');
            }

            if ($expire < Date::epoch()) {
                wp_delete_user($customerInfo->user_id);
                Customer::deleteData($customerInfo->id, 'pending_activation');
                Customer::deleteData($customerInfo->id, 'activation_last_sent');

                Customer::where('id', $customerInfo->id)->delete();

                wp_add_inline_script('booknetic-signup', 'location.href="' . htmlspecialchars(site_url()) . '";');
                return bkntc__('Expired token. Redirecting...');
            }

            wp_enqueue_style('Booknetic-font', '//fonts.googleapis.com/css?family=Poppins:200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap');
            wp_enqueue_style('booknetic-signup', Helper::assets('css/booknetic-signup.css', 'front-end'));

            wp_localize_script('booknetic-signup', 'BookneticDataSP', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'date_format' => Helper::getOption('date_format', 'Y-m-d'),
                'assets_url' => Helper::assets('/', 'front-end'),
                'localization' => []
            ]);

            Customer::deleteData($customerId, 'pending_activation');
            Customer::setData($customerId, 'activated_on', Date::epoch());

            if (isset($customerInfo->email)) {
                $user = get_user_by('email', $customerInfo->email);

                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID, true);
                do_action('wp_login', $user->user_login, $user);
            }

            return $this->view('signup' . DIRECTORY_SEPARATOR . 'signup-completed.php', ['activation_token' => $activationToken, 'redirect_to' => $redirectToUrl]);
        }

        wp_enqueue_style('Booknetic-font', '//fonts.googleapis.com/css?family=Poppins:200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap');
        wp_enqueue_style('booknetic-signup', Helper::assets('css/booknetic-signup.css', 'front-end'));

        wp_localize_script('booknetic-signup', 'BookneticDataSP', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'assets_url' => Helper::assets('/', 'front-end'),
            'localization' => []
        ]);

        if (Helper::getOption('google_recaptcha', 'off', false) == 'on') {
            $siteKey = Helper::getOption('google_recaptcha_site_key', '', false);
            $secretKey = Helper::getOption('google_recaptcha_secret_key', '', false);

            if (!empty($siteKey) && !empty($secretKey)) {
                wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . urlencode($siteKey));

                wp_localize_script('booknetic-signup', 'ReCaptcha', ['google_recaptcha_site_key' => $siteKey]);
            }
        }

        return $this->view('signup' . DIRECTORY_SEPARATOR . 'signup.php', $attrs);
    }
}