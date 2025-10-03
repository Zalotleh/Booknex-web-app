<?php

namespace BookneticApp\Providers\WpShortcodes\Concrete;

use BookneticApp\Backend\Appearance\Helpers\Theme;
use BookneticApp\Models\Appearance;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceCategory;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\WpShortcodes\WpShortcode;

class BookneticShortcode extends WpShortcode
{
    public function index($attrs): string
    {
        if (Helper::getOption('only_registered_users_can_book', 'off') == 'on' && !is_user_logged_in())
            return '<script type="application/javascript">location.href="' . Helper::getRedirectURL() . '";</script>' . bkntc__('Redirecting...');

        $attrs = empty($attrs) ? [] : $attrs;
        $info = [];
        $theme = null;

        if (isset($attrs['theme']) && is_numeric($attrs['theme']) && $attrs['theme'] > 0)
            $theme = Appearance::get($attrs['theme']);

        if (empty($theme))
            $theme = Appearance::where('is_default', '1')->fetch();

        $defaultFontFamily = 'Poppins';
        $fontFamily = $theme ? $theme['fontfamily'] : $defaultFontFamily;
        $themeId = $theme ? $theme['id'] : 0;
        $assetsHTML = '';

        if ($themeId > 0) {
            $themeCssFile = Theme::getThemeCss($themeId);

            /** @noinspection HttpUrlsUsage */
            $assetsHTML .= sprintf('<link rel="stylesheet" href="%s">', str_replace(['http://', 'https://'], '//', $themeCssFile));

            if ($fontFamily !== $defaultFontFamily)
                $assetsHTML .= sprintf('<link rel="stylesheet" href="//fonts.googleapis.com/css?family=%s:200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap">', $fontFamily);
        }

        $company_phone_number = Helper::getOption('company_phone', '');

        $steps = [
            'service' => [
                'value' => '',
                'hidden' => false,
                'loader' => 'card2',
                'title' => bkntc__('Service'),
                'head_title' => bkntc__('Select service'),
                'attrs' => ' data-service-category="' . (isset($attrs['category']) && is_numeric($attrs['category']) && $attrs['category'] > 0 ? $attrs['category'] : '') . '"'
            ],
            'staff' => [
                'value' => '',
                'hidden' => false,
                'loader' => 'card1',
                'title' => bkntc__('Staff'),
                'head_title' => bkntc__('Select staff')
            ],
            'location' => [
                'value' => isset($select_location_id) && $select_location_id > 0 ? $select_location_id : '',
                'hidden' => false,
                'loader' => 'card1',
                'title' => bkntc__('Location'),
                'head_title' => bkntc__('Select location')
            ],
            'service_extras' => [
                'value' => '',
                'hidden' => (Capabilities::tenantCan('services') == false) || Helper::getOption('show_step_service_extras', 'on') == 'off',
                'loader' => 'card2',
                'title' => bkntc__('Service Extras'),
                'head_title' => bkntc__('Select service extras')
            ],
            'information' => [
                'value' => '',
                'hidden' => false,
                'loader' => 'card3',
                'title' => bkntc__('Information'),
                'head_title' => bkntc__('Fill information')
            ],
            'cart' => [
                'value' => '',
                'hidden' => Helper::getOption('show_step_cart', 'on') == 'off',
                'loader' => 'card3',
                'title' => bkntc__('Cart'),
                'head_title' => bkntc__('Add to cart')
            ],
            'date_time' => [
                'value' => '',
                'hidden' => false,
                'loader' => 'card3',
                'title' => bkntc__('Date & Time'),
                'head_title' => bkntc__('Select Date & Time')
            ],
            'recurring_info' => [
                'value' => '',
                'hidden' => true,
                'loader' => 'card3',
                'title' => bkntc__('Recurring info'),
                'head_title' => bkntc__('Recurring info')
            ],
            'confirm_details' => [
                'value' => '',
                'hidden' => Helper::getOption('show_step_confirm_details', 'on') == 'off',
                'loader' => 'card3',
                'title' => bkntc__('Confirmation'),
                'head_title' => bkntc__('Confirm Details')
            ],
        ];

        $customStepsOrder = null;

        if (!empty($attrs['steps_order'])) {
            if (empty(array_diff(explode(',', $attrs['steps_order']), ['location', 'staff', 'service', 'service_extras', 'date_time', 'information']))) {
                $customStepsOrder = $attrs['steps_order'];
            }
        }

        $steps_order = Helper::getBookingStepsOrder(true, $customStepsOrder);

        if (!Capabilities::tenantCan('locations') || (Helper::getOption('show_step_location', 'on') == 'off') && ($location = Location::where('is_active', '1')->fetch())) {
            $steps['location']['hidden'] = true;
            $steps['location']['value'] = -1;
        }

        if (isset($_GET['location']) && is_numeric($_GET['location']) && $_GET['location'] > 0) {
            $attrs['location'] = $_GET['location'];
        }

        if (isset($attrs['location'])) {
            if (is_numeric($attrs['location']) && $attrs['location'] > 0) {
                $locationInfo = Location::get($attrs['location']);

                if ($locationInfo) {
                    $steps['location']['hidden'] = true;
                    $steps['location']['value'] = (int)$locationInfo['id'];
                }
            }

            if (is_string($attrs['location'])) {
                # Convert the 'location' string into an array of IDs
                $locationOptions = explode(",", $attrs['location']);

                # Map the array to ensure all IDs are valid integers greater than 0
                $locationOptions = array_filter(array_map(
                    fn($id) => ($id > 0 && is_numeric($id)) ? (int)trim($id) : null,
                    $locationOptions,
                ));

                $field = implode(',', $locationOptions);
                # Query the Location model to get active location IDs where the ID exists in the filtered array
                $locationOptions = Location::where('id', 'IN', $locationOptions)
                    ->where('is_active', 1)
                    ->orderBy("FIELD(id, $field)")
                    ->select('id')
                    ->fetchAll();

                # Convert array<Location> to array<$locationID:int>
                $locationOptions = array_map(
                    fn($location) => $location['id'],
                    $locationOptions
                );

                # If the options is empty do nothing
                if (!empty($locationOptions)) {
                    $steps['location']['options'] = $locationOptions;
                }
            }

        }

        if (!Capabilities::tenantCan('staff') || (Helper::getOption('show_step_staff', 'on') == 'off') && ($staff = Staff::where('is_active', '1')->fetch())) {
            $steps['staff']['hidden'] = true;
            $steps['staff']['value'] = -1;
        }

        if (isset($_GET['staff']) && is_numeric($_GET['staff']) && $_GET['staff'] > 0) {
            $attrs['staff'] = $_GET['staff'];
        }

        if (isset($attrs['staff'])) {
            if ($attrs['staff'] === 'any') {
                $steps['staff']['hidden'] = true;
                $steps['staff']['value'] = -1;
            } else if (is_numeric($attrs['staff']) && $attrs['staff'] > 0) {
                $steps['staff']['hidden'] = true;
                $steps['staff']['value'] = $attrs['staff'];
            }
        }

        if (isset($attrs['limited_booking_days'])) {
            $info['limited_booking_days'] = ( int )$attrs['limited_booking_days'];
        }

        $serviceRecurringAttrs = '';
        if (
            (
                !Capabilities::tenantCan('services') ||
                (Helper::getOption('show_step_service', 'on') == 'off')
            )
            && ($service = Service::where('is_active', '1')->fetch())
        ) {
            $steps['service']['hidden'] = true;
            $steps['service']['value'] = $service['id'];
            $serviceRecurringAttrs = ' data-is-recurring="' . (int)$service['is_recurring'] . '"';

            if ($service['is_recurring'] == 1) {
                $steps['recurring_info']['hidden'] = false;
            }
        }

        if (isset($_GET['service']) && is_numeric($_GET['service']) && $_GET['service'] > 0) {
            $attrs['service'] = $_GET['service'];
        }

        if (isset($_GET['show_service']) && is_numeric($_GET['show_service']) && $_GET['show_service'] > 0) {
            $attrs['show_service'] = $_GET['show_service'];
        }

        if (isset($attrs['service']) && is_numeric($attrs['service']) && $attrs['service'] > 0) {
            $serviceInfo = Service::get($attrs['service']);

            if ($serviceInfo) {
                $steps['service']['hidden'] = empty($attrs['show_service']);
                $steps['service']['value'] = $serviceInfo['id'];
                $serviceRecurringAttrs = ' data-is-recurring="' . (int)$serviceInfo['is_recurring'] . '"';

                if ($serviceInfo['is_recurring'] == 1) {
                    $steps['recurring_info']['hidden'] = false;
                }
            }
        }
        $steps['service']['attrs'] .= $serviceRecurringAttrs;
        $hide_confirmation_number = Helper::getOption('hide_confirmation_number', 'off') == 'on';


        if (isset($_GET['category']) && is_numeric($_GET['category']) && $_GET['category'] > 0) {
            $result = ServiceCategory::get($_GET['category']);

            $attrs['category'] = $_GET['category'];

            if ($result) {
                $steps['service']['attrs'] = ' data-service-category="' . $result->id . '"';
            }
        }

        $info = Helper::encodeInfo($info);// doit bu nedi???

        $stepOrderNumber = 1;
        $stepsArr = [];
        foreach ($steps_order as $stepId) {
            if (!isset($steps[$stepId]))
                continue;

            $step = [];
            $step['id'] = $stepId;
            $step['order_number'] = $stepOrderNumber;
            $step = array_merge($step, $steps[$stepId]);

            /* view-da istifade edilir, silme! */
            $stepsArr[] = $step;
            $stepOrderNumber += 10;
        }

        ob_start();
        require self::FRONT_DIR . 'view' . DIRECTORY_SEPARATOR . 'booking_panel/booknetic.php';
        do_action('bkntc_after_booking_panel_shortcode');
        $viewOutput = ob_get_clean();

        return $assetsHTML . $viewOutput;
    }
}