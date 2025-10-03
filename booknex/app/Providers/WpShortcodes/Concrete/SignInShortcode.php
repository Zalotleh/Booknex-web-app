<?php

namespace BookneticApp\Providers\WpShortcodes\Concrete;

use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\WpShortcodes\WpShortcode;

class SignInShortcode extends WpShortcode
{
    public function index($attrs): string
    {
        wp_enqueue_script('booknetic-signin', Helper::assets('js/booknetic-signin.js', 'front-end'), ['jquery']);


        if (Permission::userId() > 0 && !$this->isPreview()) {
            $redirectToUrl = Helper::getURLOfUsersDashboard();
            $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $parsedUrl = parse_url($currentUrl);
            $cleanUrl = "$parsedUrl[scheme]://$parsedUrl[host]$parsedUrl[path]";

            if ($redirectToUrl === $cleanUrl) {
                return '';
            }

            wp_add_inline_script('booknetic-signin', 'location.href="' . $redirectToUrl . '";');

            return bkntc__('You are already signed in. Please wait, you are being redirected...');
        }

        wp_enqueue_style('Booknetic-font', '//fonts.googleapis.com/css?family=Poppins:200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap');
        wp_enqueue_style('booknetic-signin', Helper::assets('css/booknetic-signin.css', 'front-end'));

        wp_localize_script('booknetic-signin', 'BookneticDataSI', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'assets_url' => Helper::assets('/', 'front-end'),
            'localization' => []
        ]);

        return $this->view('signin' . DIRECTORY_SEPARATOR . 'signin.php', $attrs);
    }
}