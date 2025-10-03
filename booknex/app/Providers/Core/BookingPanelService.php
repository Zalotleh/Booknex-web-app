<?php

namespace BookneticApp\Providers\Core;

use BookneticApp\Providers\Helpers\Helper;

class BookingPanelService
{

    public static function getBookingPanelAssets()
    {
        $assetsToLoad = [];
        $assetsToLoad = array_merge( $assetsToLoad, self::getBookingPanelJS() );
        $assetsToLoad = array_merge( $assetsToLoad, self::getBookingPanelCSS() );

        $assetsToLoad = apply_filters('bkntc_booking_panel_assets', $assetsToLoad);

        return $assetsToLoad;
    }

    public static function loadBookingPanelAssets()
    {
        $html = '';
        $assetsToLoad = self::getBookingPanelAssets();

        if( ! ( defined('DOING_AJAX') && DOING_AJAX ) )
        {
            /**
             * Bele yazilmaginda sebeb odu ki, bezi addonlarda inline scriptler enqueue olunan scriptlerden once verilib (Ajax ile yuklemelerde HTML ile render edilir deye teleb o olub)
             * Neticede inline script enqueue olunmush scriptin ID-sinden asilidir WP-da. Enqueue olunmalidi ilk once, sonra inline script verilmelidi.
             * Eks halda inline script ID tapmir deye elave etmir...
             * Ona gore ilk defe inline scriptden bashga hersheyi enqueue edir ashagida, sonra ise inline scriptleri elave edir.
             */
            self::enqueueAssets( array_filter( $assetsToLoad, fn ($asset) => (($asset['type']??'') != 'script') ) );
            self::enqueueAssets( array_filter( $assetsToLoad, fn ($asset) => (($asset['type']??'') == 'script') ) );
        }
        else
        {
            $html = self::renderHTMLofAssets( $assetsToLoad );
        }

        return $html;
    }

    private static function renderHTMLofAssets( $assets )
    {
        $html = '';

        foreach ( $assets AS $asset )
        {
            $id = $asset['id'] ?? '';
            $type = $asset['type'] ?? '';
            $src = $asset['src'] ?? '';
            $deps = $asset['deps'] ?? [];
            $ver = $asset['ver'] ?? false;

            if( $type === 'js' )
            {
                $html .= sprintf( '<script type="application/javascript" src="%s"></script>', $src );
            }
            else if( $type === 'css' )
            {
                $html .= sprintf( '<link rel="stylesheet" href="%s">', $src );
            }
            else if( $type === 'script' )
            {
                $html .= '<script type="text/javascript">' . $src . '</script>';
            }
        }

        return $html;
    }

    private static function enqueueAssets( $assets )
    {
        foreach ( $assets AS $asset )
        {
            $id = $asset['id'] ?? '';
            $type = $asset['type'] ?? '';
            $src = $asset['src'] ?? '';
            $deps = $asset['deps'] ?? [];
            $ver = $asset['ver'] ?? false;

            if( $type === 'js' && empty( $deps ) )
                $deps = [ 'booknetic' ];

            if( $type === 'js' )
            {
                wp_enqueue_script( $id, $src, $deps, $ver );
            }
            else if( $type === 'css' )
            {
                wp_enqueue_style($id, $src, $deps, $ver);
            }
            else if( $type === 'script' )
            {
                wp_add_inline_script($id, $src, 'before');
            }
        }
    }

    private static function getBookingPanelJS()
    {
        $assetsToLoad = [];

        $bookneticJSData = self::getBookneticJSData();

        $gooogleRecaptchaEnabled = Helper::getOption('google_recaptcha', 'off', false);
        $google_site_key = Helper::getOption('google_recaptcha_site_key', '', false);
        $google_secret_key = Helper::getOption('google_recaptcha_secret_key', '', false);

        $assetsToLoad[] = [
            'id'    =>  'booknetic',
            'type'  =>  'js',
            'src'   =>  Helper::assets('js/booknetic.js', 'front-end'),
            'deps'  =>  [ 'jquery' ]
        ];
        $assetsToLoad[] = [
            'id'    =>  'select2-bkntc',
            'type'  =>  'js',
            'src'   =>  Helper::assets('js/select2.min.js'),
            'deps'  =>  []
        ];
        $assetsToLoad[] = [
            'id'    =>  'booknetic.datapicker',
            'type'  =>  'js',
            'src'   =>  Helper::assets('js/datepicker.min.js', 'front-end'),
            'deps'  =>  []
        ];
        $assetsToLoad[] = [
            'id'    =>  'intlTelInput',
            'type'  =>  'js',
            'src'   =>  Helper::assets('js/intlTelInput.min.js', 'front-end'),
            'deps'  =>  ['jquery']
        ];
        $assetsToLoad[] = [
            'id'    =>  'jquery.nicescroll',
            'type'  =>  'js',
            'src'   =>  Helper::assets('js/jquery.nicescroll.min.js', 'front-end'),
            'deps'  =>  ['jquery']
        ];

        if( $gooogleRecaptchaEnabled == 'on' && !empty( $google_site_key ) && !empty( $google_secret_key ) )
        {
            $assetsToLoad[] = [
                'id'    =>  'google-recaptcha',
                'type'  =>  'js',
                'src'   =>  'https://www.google.com/recaptcha/api.js?render=' . urlencode($google_site_key),
            ];
            $bookneticJSData['google_recaptcha_site_key'] = $google_site_key;
        }

        $assetsToLoad[] = [
            'id'    =>  'booknetic',
            'type'  =>  'script',
            'src'   =>  'window.BookneticData = ' . json_encode( $bookneticJSData ) . ';',
        ];
        $assetsToLoad[] = [
            'id'    =>  'booknetic-recurring-appointments',
            'type'  =>  'js',
            'src'   =>  Helper::assets('js/recurring_appointments.init.js', 'front-end'),
        ];
        $assetsToLoad[] = [
            'id'    =>  'booknetic-step-step_confirm_details',
            'type'  =>  'js',
            'src'   =>  Helper::assets('js/steps/step_confirm_details.js', 'front-end'),
        ];
        $assetsToLoad[] = [
            'id'    =>  'booknetic-step-step_date_time',
            'type'  =>  'js',
            'src'   =>  Helper::assets('js/steps/step_date_time.js', 'front-end'),
        ];
        $assetsToLoad[] = [
            'id'    =>  'booknetic-step-step_information',
            'type'  =>  'js',
            'src'   =>  Helper::assets('js/steps/step_information.js', 'front-end'),
        ];
        $assetsToLoad[] = [
            'id'    =>  'booknetic-step-step_locations',
            'type'  =>  'js',
            'src'   =>  Helper::assets('js/steps/step_locations.js', 'front-end'),
        ];
        $assetsToLoad[] = [
            'id'    =>  'booknetic-step-step_recurring_info',
            'type'  =>  'js',
            'src'   =>  Helper::assets('js/steps/step_recurring_info.js', 'front-end'),
        ];
        $assetsToLoad[] = [
            'id'    =>  'booknetic-step-step_service_extras',
            'type'  =>  'js',
            'src'   =>  Helper::assets('js/steps/step_service_extras.js', 'front-end'),
        ];
        $assetsToLoad[] = [
            'id'    =>  'booknetic-step-step_services',
            'type'  =>  'js',
            'src'   =>  Helper::assets('js/steps/step_services.js', 'front-end'),
        ];
        $assetsToLoad[] = [
            'id'    =>  'booknetic-step-step_staff',
            'type'  =>  'js',
            'src'   =>  Helper::assets('js/steps/step_staff.js', 'front-end'),
        ];
        $assetsToLoad[] = [
            'id'    =>  'booknetic-step-step_cart',
            'type'  =>  'js',
            'src'   =>  Helper::assets('js/steps/step_cart.js', 'front-end'),
        ];
        $assetsToLoad[] = [
            'id'    =>  'booknetic-popup',
            'type'  =>  'js',
            'src'   =>  Helper::assets('js/booknetic-popup.js', 'front-end'),
        ];

        return $assetsToLoad;
    }

    private static function getBookingPanelCSS()
    {
        $assetsToLoad[] = [
            'id'    =>  'Booknetic-font',
            'type'  =>  'css',
            'src'   =>  '//fonts.googleapis.com/css?family=Poppins:200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap',
        ];
        $assetsToLoad[] = [
            'id'    =>  'bootstrap-booknetic',
            'type'  =>  'css',
            'src'   =>  Helper::assets('css/bootstrap-booknetic.css', 'front-end'),
        ];
        $assetsToLoad[] = [
            'id'    =>  'booknetic',
            'type'  =>  'css',
            'src'   =>  Helper::assets('css/booknetic.css', 'front-end'),
            'deps'  =>  ['bootstrap-booknetic']
        ];
        $assetsToLoad[] = [
            'id'    =>  'select2',
            'type'  =>  'css',
            'src'   =>  Helper::assets('css/select2.min.css'),
        ];
        $assetsToLoad[] = [
            'id'    =>  'intlTelInput',
            'type'  =>  'css',
            'src'   =>  Helper::assets('css/intlTelInput.min.css', 'front-end'),
        ];
        $assetsToLoad[] = [
            'id'    =>  'select2-bootstrap',
            'type'  =>  'css',
            'src'   =>  Helper::assets('css/select2-bootstrap.css'),
        ];
        $assetsToLoad[] = [
            'id'    =>  'booknetic.datapicker',
            'type'  =>  'css',
            'src'   =>  Helper::assets('css/datepicker.min.css', 'front-end'),
        ];
        $assetsToLoad[] = [
            'id'    =>  'booknetic-popup',
            'type'  =>  'css',
            'src'   =>  Helper::assets('css/booknetic-popup.css', 'front-end'),
        ];

        return $assetsToLoad;
    }

    private static function getBookneticJSData(): array
    {
        $localization = [
            'Services'              => bkntc__('Services'),
            'Service'               => bkntc__('Service'),
            'Fill information'      => bkntc__('Fill information'),
            'Information'           => bkntc__('Information'),
            'Confirmation'          => bkntc__('Confirmation'),

            // months
            'January'               => bkntc__('January'),
            'February'              => bkntc__('February'),
            'March'                 => bkntc__('March'),
            'April'                 => bkntc__('April'),
            'May'                   => bkntc__('May'),
            'June'                  => bkntc__('June'),
            'July'                  => bkntc__('July'),
            'August'                => bkntc__('August'),
            'September'             => bkntc__('September'),
            'October'               => bkntc__('October'),
            'November'              => bkntc__('November'),
            'December'              => bkntc__('December'),

            //days of week
            'Mon'                   => bkntc__('Mon'),
            'Tue'                   => bkntc__('Tue'),
            'Wed'                   => bkntc__('Wed'),
            'Thu'                   => bkntc__('Thu'),
            'Fri'                   => bkntc__('Fri'),
            'Sat'                   => bkntc__('Sat'),
            'Sun'                   => bkntc__('Sun'),

            // select placeholders
            'select'                => bkntc__('Select...'),
            'searching'				=> bkntc__('Searching...'),

            // messages
            'select_location'       => bkntc__('Please select location.'),
            'select_staff'          => bkntc__('Please select staff.'),
            'select_service'        => bkntc__('Please select service'),
            'select_week_days'      => bkntc__('Please select week day(s)'),
            'date_time_is_wrong'    => bkntc__('Please select week day(s) and time(s) correctly'),
            'select_start_date'     => bkntc__('Please select start date'),
            'select_end_date'       => bkntc__('Please select end date'),
            'select_date'           => bkntc__('Please select date.'),
            'select_time'           => bkntc__('Please select time.'),
            'select_available_time' => bkntc__('Please select an available time'),
            'select_available_date' => bkntc__('Please select an available date'),
            'fill_all_required'     => bkntc__('Please fill in all required fields correctly!'),
            'email_is_not_valid'    => bkntc__('Please enter a valid email address!'),
            'phone_is_not_valid'    => bkntc__('Please enter a valid phone number!'),
            'Select date'           => bkntc__('Select date'),
            'NEXT STEP'             => bkntc__('NEXT STEP'),
            'CONFIRM BOOKING'       => bkntc__('CONFIRM BOOKING'),
            'Activation link has been sent!' => bkntc__('Activation link has been sent!'),
        ];

        return [
            'ajax_url'		            => admin_url( 'admin-ajax.php' ),
            'assets_url'	            => Helper::assets('/', 'front-end') ,
            'date_format'	            => Helper::getOption('date_format', 'Y-m-d'),
            'week_starts_on'            => Helper::getOption('week_starts_on', 'sunday') == 'monday' ? 'monday' : 'sunday',
            'client_time_zone'	        => htmlspecialchars(Helper::getOption('client_timezone_enable', 'off')),
            'skip_extras_step_if_need'  => htmlspecialchars(Helper::getOption('skip_extras_step_if_need', 'on')),
            'localization'              => apply_filters('bkntc_frontend_localization' , $localization ),
            'tenant_id'                 => Permission::tenantId(),
            'settings'                  => [
                'redirect_users_on_confirm' => Helper::getOption( 'redirect_users_on_confirm', 'off' ) === 'on',
                'redirect_users_on_confirm_url' => Helper::getOption( 'redirect_users_on_confirm_url', '' )
            ]
        ];
    }

}