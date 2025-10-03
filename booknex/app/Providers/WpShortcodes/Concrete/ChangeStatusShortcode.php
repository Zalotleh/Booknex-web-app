<?php

namespace BookneticApp\Providers\WpShortcodes\Concrete;

use BookneticApp\Backend\Appointments\Helpers\AppointmentChangeStatus;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\WpShortcodes\WpShortcode;

class ChangeStatusShortcode extends WpShortcode
{
    public function index($attrs): string
    {
        $attrs = empty($attrs) ? [] : $attrs;

        $token = Helper::_get('bkntc_token', '', 'string');
        $validateToken = AppointmentChangeStatus::validateToken($token);
        $isPreviewMode = false;

        if (Permission::userId() > 0 && $this->isPreview()) {
            $isPreviewMode = true;
            $token = base64_encode('booknetic') . '.' . base64_encode(json_encode(['title' => '{status}']));
        }

        if ($isPreviewMode !== true && $validateToken !== true) {
            return $validateToken;
        }

        wp_enqueue_script('booknetic-change-status-blocks', Helper::assets('js/booknetic-change-status.js', 'front-end'), ['jquery']);
        wp_enqueue_style('Booknetic-font', '//fonts.googleapis.com/css?family=Poppins:200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap');
        wp_enqueue_style('booknetic-change-status-blocks', Helper::assets('css/booknetic-change-status.css', 'front-end'));

        wp_localize_script('booknetic-change-status-blocks', 'BookneticChangeStatusData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'assets_url' => Helper::assets('/', 'front-end'),
            'date_format' => Helper::getOption('date_format', 'Y-m-d'),
            'week_starts_on' => Helper::getOption('week_starts_on', 'sunday') == 'monday' ? 'monday' : 'sunday',
            'client_timezone' => htmlspecialchars(Helper::getOption('client_timezone_enable', 'off')),
            'tz_offset_param' => htmlspecialchars(Helper::_get('client_time_zone', '-', 'str')),
            'localization' => [
                // months
                'January' => bkntc__('January'),
                'February' => bkntc__('February'),
                'March' => bkntc__('March'),
                'April' => bkntc__('April'),
                'May' => bkntc__('May'),
                'June' => bkntc__('June'),
                'July' => bkntc__('July'),
                'August' => bkntc__('August'),
                'September' => bkntc__('September'),
                'October' => bkntc__('October'),
                'November' => bkntc__('November'),
                'December' => bkntc__('December'),

                //days of week
                'Mon' => bkntc__('Mon'),
                'Tue' => bkntc__('Tue'),
                'Wed' => bkntc__('Wed'),
                'Thu' => bkntc__('Thu'),
                'Fri' => bkntc__('Fri'),
                'Sat' => bkntc__('Sat'),
                'Sun' => bkntc__('Sun'),

                // select placeholders
                'select' => bkntc__('Select...'),
                'searching' => bkntc__('Searching...'),
            ],
            'token' => $token,
        ]);

        $attrs['isSaaS'] = Helper::isSaaSVersion();
        $attrs['companyImage'] = Helper::profileImage(Helper::getOption('company_image', ''), 'Settings');
        $attrs['uploadLogoCapability'] = Capabilities::tenantCan('upload_logo_to_booking_panel');
        $attrs['displayLogo'] = Helper::getOption('display_logo_on_booking_panel', 'off') == 'on';

        return $this->view('change_status/index.php', $attrs);

    }

}