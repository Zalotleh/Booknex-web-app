<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Backend\Settings\Helpers\LocalizationService;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\Staff;
use BookneticApp\Models\Timesheet;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Session;
use BookneticApp\Providers\UI\Abstracts\AbstractMenuUI;
use BookneticApp\Providers\UI\MenuUI;

/**
 * @var $currentModule
 * @var $currentAction
 * @var $fullViewPath
*/

$localization = [
    // Appearance
    'are_you_sure'					=> bkntc__('Are you sure?'),

    // Appointments
    'select'						=> bkntc__('Select...'),
    'searching'						=> bkntc__('Searching...'),
    'firstly_select_service'		=> bkntc__('Please firstly choose a service!'),
    'fill_all_required'				=> bkntc__('Please fill in all required fields correctly!'),
    'timeslot_is_not_available'		=> bkntc__('This time slot is not available!'),
    'link_copied'                   => bkntc__('Link copied!'),
    'email_is_not_valid'            => bkntc__('Please enter a valid email address!'),
    'phone_is_not_valid'            => bkntc__('Please enter a valid phone number!'),

    // Customers
    'Deleted'                       => bkntc__('Deleted'),

    // Base
    'are_you_sure_want_to_delete'	=> bkntc__('Are you sure you want to delete?'),
    'rows_deleted'					=> bkntc__('Rows deleted!'),
    'delete'                        => bkntc__('DELETE'),
    'cancel'                        => bkntc__('CANCEL'),
    'dear_user'                     => bkntc__('Dear user'),
    'fill_form_correctly'			=> bkntc__('Fill the form correctly!'),
    'saved_successfully'			=> bkntc__('Saved succesfully!'),
    'type_email'   					=> bkntc__('Please type email!'),

    // calendar
    'group_appointment'				=> bkntc__('Group appointment'),
    'new_appointment'				=> bkntc__('NEW APPOINTMENT'),

    // Dashboard
    'loading'					    => bkntc__('Loading...'),
    'bookings_on'                   => bkntc__('bookings on'),
    'Apply'					        => bkntc__('Apply'),
    'Cancel'					    => bkntc__('Cancel'),
    'From'					        => bkntc__('From'),
    'To'					        => bkntc__('To'),

    // Services
    'delete_service_extra'			=> bkntc__('Are you sure that you want to delete this service extra?'),
    'no_more_staff_exist'			=> bkntc__('No more Staff exists for select!'),
    'choose_staff_first'			=> bkntc__('Please choose the staff first'),
    'staff_empty'					=> bkntc__('Staff field cannot be empty'),
    'select_staff'					=> bkntc__('Choose the staff to add'),
    'delete_special_day'			=> bkntc__('Are you sure to delete this special day?'),
    'times_per_month'				=> bkntc__('time(s) per month'),
    'times_per_week'				=> bkntc__('time(s) per week'),
    'every_n_day'					=> bkntc__('Every n day(s)'),
    'delete_service'				=> bkntc__('Are you sure you want to delete this service?'),
    'delete_category'				=> bkntc__('Are you sure you want to delete this category?'),
    'category_name'					=> bkntc__('Category name'),
    'add_category'			        => bkntc__('ADD CATEGORY'),
    'save'			                => bkntc__('SAVE'),
    'no_service_to_show'            => bkntc__('No service to show'),
    'edit_order'                    => bkntc__('EDIT ORDER'),
    'choose_staff'                  => bkntc__('Please choose at least one staff!'),

    //Extra Services
    'service_name'			        => bkntc__('Service name'),
    'min_quantity'			        => bkntc__('Min. quantity'),
    'max_quantity'			        => bkntc__('Max. quantity'),
    'category'			            => bkntc__('Category'),
    'price'			                => bkntc__('Price'),
    'hide_price_booking_panel'      => bkntc__('Hide price in booking panel:'),
    'hide_duration_booking_panel'	=> bkntc__('Hide duration in booking panel:'),
    'duration'			            => bkntc__('Duration'),
    'note'			                => bkntc__('Note'),
    'save_extra'			        => bkntc__('SAVE EXTRA'),
    'default_zero_means_there_is_no_minimum_requirement'	=> bkntc__('Default 0 means there is no minimum requirement.'),
    'to_add_a_category_enter_name_and_press_enter'	=> bkntc__("To create a category, simply enter your desired category name in the field and press 'Enter'."),
    'sure_to_delete_extra_category' => bkntc__( 'Are you sure that you want to delete this category?' ),

    // months
    'January'               		=> bkntc__('January'),
    'February'              		=> bkntc__('February'),
    'March'                 		=> bkntc__('March'),
    'April'                 		=> bkntc__('April'),
    'May'                   		=> bkntc__('May'),
    'June'                  		=> bkntc__('June'),
    'July'                  		=> bkntc__('July'),
    'August'                		=> bkntc__('August'),
    'September'             		=> bkntc__('September'),
    'October'               		=> bkntc__('October'),
    'November'              		=> bkntc__('November'),
    'December'              		=> bkntc__('December'),

    //days of week
    'Mon'                   		=> bkntc__('Mon'),
    'Tue'                   		=> bkntc__('Tue'),
    'Wed'                   		=> bkntc__('Wed'),
    'Thu'                   		=> bkntc__('Thu'),
    'Fri'                   		=> bkntc__('Fri'),
    'Sat'                   		=> bkntc__('Sat'),
    'Sun'                   		=> bkntc__('Sun'),

    'session_has_expired'           => bkntc__('Your session has expired. Please refresh the page and try again.'),
    'graphic_view'                  => bkntc__('Graphic view'),
    'keywords'                      => bkntc__('Keywords'),

    'update_appointment_prices'     => bkntc__('Appointment prices are different from the service price, do you want to update appointment prices?'),
    'update'                        => bkntc__('Update'),
    'dont'                          => bkntc__('Don\'t'),
    'reschedule'					=> bkntc__('Reschedule'),
    'rescheduled_successfully'      => bkntc__('Appointment has been successfully rescheduled!'),
    'reschedule_appointment_confirm'=> bkntc__('Would you like to reschedule the appointment?'),
    'run_workflow_reschedule'       => bkntc__('Run workflows on reschedule'),
    'something_went_wrong'          => bkntc__('Something went wrong...'),
    'copied_to_clipboard'			=> bkntc__( 'Copied to clipboard' ),
    'really_want_to_delete'			=> bkntc__( 'Are you really want to delete?' ),
    'join_beta_approval' => bkntc__( 'Congratulations, you are a beta user!' ),
    'leave_beta_approval' => bkntc__( 'Success! You have successfully opted out of the beta program' ),
    'join_beta' => bkntc__( 'Congratulations, you are a beta user!' ),

    // Onboarding
    "next" => bkntc__("Next"),
    "skip" => bkntc__("Skip"),
    "finish" => bkntc__("Finish")
];
$localization = apply_filters('bkntc_localization' , $localization );

$servicesIsOk       = Service::count() > 0;
$businessHoursIsOk  = Timesheet::where('service_id', 'is', null)->where('staff_id', 'is', null)->count() > 0;
$companyDetailsIsOk = Helper::getOption('company_name', '') != '';
$guidePanelDisabled = isset( $_COOKIE[ 'guide_panel_hidden' ] ) ? $_COOKIE[ 'guide_panel_hidden' ] : 0;

$isRtl = Helper::isRTLLanguage(0, true, Session::get('active_language', get_locale()));

$profileImage = get_avatar_url(get_current_user_id());

if ( Helper::isSaaSVersion() && Helper::isTenant() ) {
    $profileImage = Helper::pictureUrl( Permission::tenantInf()->picture );
}
?>
<!DOCTYPE html>
<html <?php echo $isRtl?'dir="rtl"':''; ?>>
<head>
    <title><?php echo htmlspecialchars(Helper::getOption('backend_title', 'Booknetic', false))?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css?family=Poppins:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css?ver=5.0.2" type="text/css">

    <link rel="stylesheet" href="<?php echo Helper::assets('css/bootstrap.min.css')?>" type="text/css">

    <link rel="stylesheet" href="<?php echo Helper::assets('css/main.css')?>" type="text/css">
    <link rel="stylesheet" href="<?php echo Helper::assets('css/notification.css')?>" type="text/css">

    <link rel="stylesheet" href="<?php echo Helper::assets('css/animate.css')?>" type="text/css">
    <link rel="stylesheet" href="<?php echo Helper::assets('css/select2.min.css')?>" type="text/css">
    <link rel="stylesheet" href="<?php echo Helper::assets('css/select2-bootstrap.css')?>" type="text/css">
    <link rel="stylesheet" href="<?php echo Helper::assets('css/bootstrap-datepicker.css')?>" type="text/css">

    <script type="application/javascript" src="<?php echo Helper::assets('js/jquery-3.3.1.min.js')?>"></script>
    <script type="application/javascript" src="<?php echo Helper::assets('js/popper.min.js')?>"></script>
    <script type="application/javascript" src="<?php echo Helper::assets('js/bootstrap.min.js')?>"></script>
    <script type="application/javascript" src="<?php echo Helper::assets('js/select2.min.js')?>"></script>
    <script type="application/javascript" src="<?php echo Helper::assets('js/jquery-ui.js')?>"></script>
    <script type="application/javascript" src="<?php echo Helper::assets('js/jquery.ui.touch-punch.min.js')?>"></script>
    <script type="application/javascript" src="<?php echo Helper::assets('js/bootstrap-datepicker.min.js')?>"></script>
    <script type="application/javascript" src="<?php echo Helper::assets('js/jquery.nicescroll.min.js')?>"></script>
    <script type="application/javascript" src="<?php echo Helper::assets('js/notification.js')?>"></script>

    <link rel="shortcut icon" href="<?php echo Helper::profileImage( Helper::getOption('whitelabel_logo_sm', 'logo-sm', false), 'Base')?>">

    <script>
        const BACKEND_SLUG = '<?php echo Helper::getSlugName(); ?>';
        const TENANT_CAN_DYNAMIC_TRANSLATIONS = <?php echo json_encode( Capabilities::tenantCan( 'dynamic_translations' ) ); ?>;
    </script>

    <script src="<?php echo Helper::assets('js/booknetic.js')?>"></script>
    <script src="<?php echo Helper::assets('js/price_helper.js')?>"></script>

    <script>
        var ajaxurl			            = '?page=<?php echo \BookneticApp\Providers\Core\Backend::getSlugName()?>&ajax=1',
            pageURL                     = "<?php echo admin_url() ?>",
            currentModule	            = "<?php echo htmlspecialchars( Route::getCurrentModule() )?>",
            assetsUrl		            = "<?php echo Helper::assets('')?>",
            frontendAssetsUrl	        = "<?php echo Helper::assets('', 'front-end')?>",
            weekStartsOn	            = "<?php echo Helper::getOption('week_starts_on', 'sunday') == 'monday' ? 'monday' : 'sunday'?>",
            dateFormat  	            = "<?php echo htmlspecialchars(Helper::getOption('date_format', 'Y-m-d'))?>",
            timeFormat  	            = "<?php echo htmlspecialchars(Helper::getOption('time_format', 'H:i'))?>",
            localization	            = <?php echo json_encode($localization)?>,
            isSaaSVersion	            = <?php echo json_encode(Helper::isSaaSVersion()) ?>,
            fcLocale			        = "<?php echo strtolower(str_replace('_', '-', Helper::getLocaleForTenant())) ?>",
            documentationURL            = "<?php echo htmlspecialchars(Helper::getOption('documentation_url', 'https://www.booknetic.com/documentation/', false))?>",
            price_settings              = {
                price_number_format: "<?php echo (int)Helper::getOption('price_number_format', '1')?>",
                price_number_of_decimals: "<?php echo (int)Helper::getOption('price_number_of_decimals', '2')?>",
                currency_format: "<?php echo (int)Helper::getOption('currency_format', '1')?>",
                currency_symbol: "<?php echo htmlspecialchars( Helper::getOption('currency_symbol', '$') )?>",
                currency: "<?php echo htmlspecialchars( Helper::getOption('currency', 'USD') )?>",
                currencies: <?php print json_encode(Helper::currencies())?>
            };
    </script>

    <?php do_action( 'bkntc_enqueue_assets', $currentModule, $currentAction, $fullViewPath );?>

    <?php if ( Helper::canShowTemplates() ): ?>
        <script src="<?php echo Helper::assets( 'js/load-template-selection-popup.js' )?>"></script>
    <?php endif; ?>

</head>
<body style="overflow: auto" class="nice-scrollbar-primary <?php echo $isRtl ? 'rtl ' : ''; ?>minimized_left_menu-">

<?php $url = Helper::showChangelogs(); if ( ! empty( $url ) ): ?>
    <!-- Changelogs popup after plugin updated -->
    <link rel="stylesheet" href="<?php echo Helper::assets('css/changelogs_popup.css')?>">
    <script type="application/javascript" src="<?php echo Helper::assets( 'js/changelogs_popup.js' ); ?>"></script>
    <div id="changelogsPopup" class="changelogs-popup-container">
        <div class="changelogs-popup">
            <div id="changelogsPopupClose" class="changelogs-popup-close">
                <i class="fas fa-times"></i>
            </div>
            <iframe src="<?php echo $url; ?>"></iframe>
        </div>
    </div>
<?php endif; ?>

<div id="booknetic_progress" class="booknetic_progress_waiting booknetic_progress_done"><dt></dt><dd></dd></div>

<div class="left_side_menu">

    <div class="l_m_head">
        <img src="<?php echo Helper::profileImage( Helper::getOption('whitelabel_logo', 'logo', false), 'Base')?>" class="head_logo_xl">
        <img src="<?php echo Helper::profileImage( Helper::getOption('whitelabel_logo_sm', 'logo-sm', false), 'Base')?>" class="head_logo_sm">
    </div>

    <?php if ( MenuUI::isset( 'boostore', AbstractMenuUI::MENU_TYPE_BOOSTORE ) ): ?>
        <?php $boo = MenuUI::get( 'boostore', AbstractMenuUI::MENU_TYPE_BOOSTORE ); ?>
        <div class="boostore-button-container">
            <a href="<?php echo $boo->getLink(); ?>" class="boostore-button-body">
                <div class="boostore-button-text">
                    <?php echo $boo->getTitle() ?>
                </div>
                <div class="boostore-button-icon">
                    <img src="<?php echo $boo->getIcon() ?>" alt="">
                </div>
            </a>
        </div>
    <?php endif; ?>

    <div class="d-md-none language-chooser-bar-in-menu">
        <?php if(
            Helper::isSaaSVersion() &&
            Helper::getOption('enable_language_switcher', 'off', false) == 'on' &&
            count( Helper::getOption('active_languages', [], false) ) > 1
        ):?>
            <div class="language-chooser-bar">
                <div class="language-chooser" data-toggle="dropdown" aria-haspopup="false" aria-expanded="false">
                    <span><?php echo htmlspecialchars(LocalizationService::getLanguageName( Session::get('active_language', get_locale()) ))?></span>
                    <i class="fa fa-angle-down"></i>
                </div>
                <div class="dropdown-menu dropdown-menu-right row-actions-area language-switcher-select">
                    <?php foreach ( Helper::getOption('active_languages', [], false) AS $active_language ):?>
                        <div data-language-key="<?php echo htmlspecialchars($active_language)?>" class="dropdown-item info_action_btn"><?php echo htmlspecialchars(LocalizationService::getLanguageName( $active_language ))?></div>
                    <?php endforeach;?>
                </div>
            </div>
        <?php endif;?>
    </div>

    <ul class="l_m_nav">
	    <?php foreach ( MenuUI::getItems( AbstractMenuUI::MENU_TYPE_LEFT ) as $menu ): ?>
            <li class="l_m_nav_item
                <?php echo $menu->isActive() ? 'active_menu' : ''; ?>
                <?php echo $hasActiveChild = array_reduce( $menu->getSubItems(), fn( $carry, $sub ) => $carry || $sub->isActive() ) ? 'has_active_child' : ''?>
                <?php echo( ! empty( $menu->getSubItems() ) ? ' is_parent" data-id="' . $menu->getSlug() : '' ); ?>"
            >
                <a href="<?php echo $menu->getLink(); ?>" class="l_m_nav_item_link"
                   target="<?php echo $menu->getLinkTarget() ?>">
                    <i class="l_m_nav_item_icon <?php echo $menu->getIcon(); ?>"></i>
                    <span class="l_m_nav_item_text"><?php echo $menu->getTitle(); ?></span>
				    <?php if ( ! empty( $menu->getSubItems() ) ): ?>
                        <i class="l_m_nav_item_icon is_collapse_icon fa fa-chevron-<?php echo $hasActiveChild ? 'up' : 'down' ?>"></i>
				    <?php endif; ?>
                </a>
            </li>
		    <?php if ( ! empty( $menu->getSubItems() ) ): ?>
			    <?php foreach ( $menu->getSubItems() as $submenu ): ?>
                    <li class="l_m_nav_item <?php echo $submenu->isActive() ? 'active_menu' : ''; ?> is_sub"
                        data-parent-id="<?php echo $menu->getSlug(); ?>">
                        <a href="<?php echo $submenu->getLink(); ?>" class="l_m_nav_item_link">
                            <i class="l_m_nav_item_icon <?php echo $submenu->getIcon(); ?>"></i>
                            <span class="l_m_nav_item_text"><?php echo $submenu->getTitle(); ?></span>
                        </a>
                    </li>
			    <?php endforeach; ?>
		    <?php endif; ?>
	    <?php endforeach; ?>

        <?php if( !Helper::isSaaSVersion() && Capabilities::userCan( 'boostore' ) ): ?>
            <li class="l_m_nav_item d-md-none">
                <a href="admin.php?page=booknetic&module=boostore" class="l_m_nav_item_link">
                    <i class="l_m_nav_item_icon fa fa-puzzle-piece"></i>
                    <span class="l_m_nav_item_text"><?php echo bkntc__('Boostore')?></span>
                </a>
            </li>
        <?php endif; ?>

        <li class="l_m_nav_item d-md-none">
            <?php
            if ( ! Helper::isSaaSVersion() && Capabilities::userCan( 'back_to_wordpress' ) ): ?>
                <a href="index.php" class="l_m_nav_item_link">
                    <i class="l_m_nav_item_icon fab fa-wordpress"></i>
                    <span class="l_m_nav_item_text"><?php echo bkntc__('Back to WordPress')?></span>
                </a>
            <?php elseif ( Permission::isAdministrator() ): //only tenants can see this button, not their staff ?>
                <a href="#" class="l_m_nav_item_link share_your_page_btn">
                    <i class="l_m_nav_item_icon fa fa-share"></i>
                    <span class="l_m_nav_item_text"><?php echo bkntc__('Share your page ')?></span>
                </a>
            <?php endif; ?>
        </li>

    </ul>

</div>

<div class="top_side_menu">
    <div class="t_m_left">
        <?php if ( Helper::isSaaSVersion() && Permission::isAdministrator() ): ?>
            <button class="btn btn-default btn-lg d-md-inline-block d-none share_your_page_btn" type="button">
                <i class="fa fa-share mr-2"></i> <span><?php echo bkntc__( 'Share your page' ) ?></span></button>
        <?php endif; ?>

        <?php foreach ( MenuUI::getItems( AbstractMenuUI::MENU_TYPE_TOP_LEFT ) as $menu ): ?>
            <a class="btn btn-default btn-lg d-md-inline-block d-none" href="<?php echo $menu->getLink(); ?>"><i class="<?php echo $menu->getIcon(); ?> pr-2"></i>
                <span><?php echo $menu->getTitle(); ?></span>
            </a>
        <?php endforeach; ?>

        <button class="btn btn-default btn-lg d-md-none" type="button" id="open_menu_bar"><i class="fa fa-bars"></i>
        </button>
    </div>
    <div class="t_m_right">
    <div class="booknetic_notification_area">
        <button class="booknetic_notification_button d-none">
            <img src="<?php echo Helper::icon('bell-icon.svg') ?>" alt="bell icon"/>
            <span class="notification-badge"></span>
        </button>
        <div class="booknetic_notification_panel" style="display: none;">
            <div class="booknetic_notification_pointer"></div>
            <div class="notification_panel_header">
                <div>
                    <h5><?php echo bkntc__('Notifications')?></h5>
                    <p class="notifcation_panel_header-count">(0)</p>
                </div>
                <button class="notification_panel_clear_btn"><?php echo bkntc__('Mark all as read')?></button>
            </div>
            <div class="notification-body">
                <div class="notification-carousel-wrapper">
                        <div class="notification-carousel-item notification-survey">
                            <div class="notification-carousel-item-header">
                                <p>
                                    <img src="<?php echo Helper::icon('survey_icon.svg') ?>" alt="survey icon"/>
                                    <span>Survey</span>
                                </p>
                               <button><img src="<?php echo Helper::icon('notification_close.svg') ?>" alt="close icon"/></button>
                            </div>
                            <div class="notification-carousel-item-content">We need your help. <b>Let’s Participate our survey</b></div>
                            <a href="">Learn More
                                <span class="learn_more_icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                        <path d="M4.66666 11.3333L11.3333 4.66666M11.3333 4.66666H4.66666M11.3333 4.66666V11.3333" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </a>
                        </div>
                        <div class="notification-carousel-item notification-addon">
                            <div class="notification-carousel-item-header">
                                <p>
                                    <img src="<?php echo Helper::icon('addon_icon.svg') ?>" alt="addon icon"/>
                                    <span>Addon</span>
                                </p>
                               <button><img src="<?php echo Helper::icon('notification_close.svg') ?>" alt="close icon"/></button>
                            </div>
                            <div class="notification-carousel-item-content">We’ve just released <b>Package add-on. </b>Check it out.</div>
                            <a href="">Learn More
                                <span class="learn_more_icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                        <path d="M4.66666 11.3333L11.3333 4.66666M11.3333 4.66666H4.66666M11.3333 4.66666V11.3333" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </a>
                        </div>
                </div>
                <div class="notification-card">
                    <div class="notification-card_header">
                        <p>
                             <img src="<?php echo Helper::icon('update_icon.svg') ?>" alt="bell icon"/>
                             <span><?php echo bkntc__('Update')?></span>
                        </p>
                        <span class="d-flex align-items-center"><?php echo bkntc__('2 min ago')?><div class="notification_time_badge ml-2 d-none"></div></span>
                    </div>
                    <div class="notification-content">There is an update available. Version <b>2.16.24</b></div>
                    <a href="">Learn More <span class="learn_more_icon"><img src="<?php echo Helper::icon('learn_more.svg') ?>" alt="Learn more"/></span></a>
                </div>

                <div class="notification-card unread-notification">
                    <div class="notification-card_header">
                        <p>
                             <img src="<?php echo Helper::icon('update_icon.svg') ?>" alt="bell icon"/>
                             <span><?php echo bkntc__('Update')?></span>
                        </p>
                        <span class="d-flex align-items-center"><?php echo bkntc__('2 min ago')?><div class="notification_time_badge ml-2"></div></span>
                    </div>
                    <div class="notification-content">There is an update available. Version <b>2.16.24</b></div>
                    <a href="">Learn More <span class="learn_more_icon"><img src="<?php echo Helper::icon('learn_more.svg') ?>" alt="Learn more"/></span></a>
                </div>

                <div class="notification-card unread-notification">
                    <div class="notification-card_header">
                        <p>
                             <img src="<?php echo Helper::icon('update_icon.svg') ?>" alt="bell icon"/>
                             <span><?php echo bkntc__('Update')?></span>
                        </p>
                        <span class="d-flex align-items-center"><?php echo bkntc__('2 min ago')?><div class="notification_time_badge ml-2"></div></span>
                    </div>
                    <div class="notification-content">There is an update available. Version <b>2.16.24</b></div>
                    <a href="">Learn More <span class="learn_more_icon"><img src="<?php echo Helper::icon('learn_more.svg') ?>" alt="Learn more"/></span></a>
                </div>

                <div class="notification-card">
                    <div class="notification-card_header">
                        <p>
                             <img src="<?php echo Helper::icon('update_icon.svg') ?>" alt="bell icon"/>
                             <span><?php echo bkntc__('Update')?></span>
                        </p>
                        <span class="d-flex align-items-center"><?php echo bkntc__('2 min ago')?><div class="notification_time_badge ml-2 d-none"></div></span>
                    </div>
                    <div class="notification-content">There is an update available. Version <b>2.16.24</b></div>
                    <a href="">Learn More <span class="learn_more_icon"><img src="<?php echo Helper::icon('learn_more.svg') ?>" alt="Learn more"/></span></a>
                </div>
            </div>
        </div>
    </div>
        <?php if ( Permission::isSuperAdministrator() ): ?>
            <div class="booknetic_help_center_icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M8.33333 7.0852C8.48016 6.6678 8.76998 6.31584 9.15144 6.09165C9.53291 5.86745 9.98141 5.7855 10.4175 5.86031C10.8536 5.93511 11.2492 6.16184 11.5341 6.50034C11.8191 6.83884 11.975 7.26726 11.9744 7.70973C11.9744 8.9588 10.1008 9.58333 10.1008 9.58333M10.1249 12.0833H10.1333M8.25 16L9.46667 17.6222C9.6476 17.8635 9.73807 17.9841 9.84897 18.0272C9.94611 18.065 10.0539 18.065 10.151 18.0272C10.2619 17.9841 10.3524 17.8635 10.5333 17.6222L11.75 16C11.9943 15.6743 12.1164 15.5114 12.2654 15.3871C12.4641 15.2213 12.6986 15.104 12.9504 15.0446C13.1393 15 13.3429 15 13.75 15C14.9149 15 15.4973 15 15.9567 14.8097C16.5693 14.556 17.056 14.0693 17.3097 13.4567C17.5 12.9973 17.5 12.4149 17.5 11.25V6.5C17.5 5.09987 17.5 4.3998 17.2275 3.86502C16.9878 3.39462 16.6054 3.01217 16.135 2.77248C15.6002 2.5 14.9001 2.5 13.5 2.5H6.5C5.09987 2.5 4.3998 2.5 3.86502 2.77248C3.39462 3.01217 3.01217 3.39462 2.77248 3.86502C2.5 4.3998 2.5 5.09987 2.5 6.5V11.25C2.5 12.4149 2.5 12.9973 2.6903 13.4567C2.94404 14.0693 3.43072 14.556 4.04329 14.8097C4.50272 15 5.08515 15 6.25 15C6.65715 15 6.86072 15 7.04959 15.0446C7.30141 15.104 7.53593 15.2213 7.73458 15.3871C7.88357 15.5114 8.00571 15.6743 8.25 16Z" stroke="#626C76" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <div class="booknetic_help_center_dropdown" style="display: none">
                    <div class="booknetic_help_center_dropdown_pointer_wrapper">
                        <div class="booknetic_help_center_dropdown_pointer"></div>
                    </div>
                    <div class="booknetic_help_center_dropdown_body">
                        <div class="booknetic_contact_us booknetic_help_center_category">
                            <p class="booknetic_help_center_category_text"><?php echo bkntc__( "Contact Us" ) ?></p>
                            <div class="booknetic_discord_wrapper booknetic_item" href="https://discord.com/invite/CGaDJHDvDS">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <g clip-path="url(#clip0_2368_95)">
                                        <mask id="mask0_2368_95" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="20" height="20">
                                            <path d="M19.1666 9.99998C19.1666 4.93737 15.0625 0.833313 9.99992 0.833313C4.93731 0.833313 0.833252 4.93737 0.833252 9.99998C0.833252 15.0626 4.93731 19.1666 9.99992 19.1666C15.0625 19.1666 19.1666 15.0626 19.1666 9.99998Z" fill="white"/>
                                        </mask>
                                        <g mask="url(#mask0_2368_95)">
                                            <path d="M8.10299 9.55322C7.50585 9.55322 7.03442 10.0728 7.03442 10.7068C7.03442 11.3408 7.51632 11.8605 8.10299 11.8605C8.70017 11.8605 9.17158 11.3408 9.17158 10.7068C9.182 10.0728 8.70017 9.55322 8.10299 9.55322ZM11.9268 9.55322C11.3297 9.55322 10.8582 10.0728 10.8582 10.7068C10.8582 11.3408 11.3402 11.8605 11.9268 11.8605C12.5239 11.8605 12.9954 11.3408 12.9954 10.7068C12.9954 10.0728 12.5239 9.55322 11.9268 9.55322Z" fill="#292D32"/>
                                            <path d="M17.019 0.833313H2.98087C1.79706 0.833313 0.833252 1.78947 0.833252 2.97428V17.0256C0.833252 18.2105 1.79706 19.1666 2.98087 19.1666H14.8608H15.6466H16.1808H17.3677C18.3612 19.1666 19.1666 18.3612 19.1666 17.3677V2.97428C19.1666 1.78947 18.2028 0.833313 17.019 0.833313ZM13.2403 14.3917C13.0735 14.4053 12.9123 14.3323 12.8053 14.2037C12.7598 14.1489 12.7073 14.0856 12.6508 14.0171C12.475 13.8037 12.5792 13.4806 12.8327 13.3701C12.9518 13.3181 13.0622 13.2631 13.164 13.2066C13.4473 13.0491 13.2793 12.8291 12.9752 12.9412C12.4513 13.1595 11.9485 13.305 11.4561 13.3881C10.4504 13.5751 9.5285 13.5232 8.74275 13.3777C8.14564 13.2634 7.6323 13.0971 7.20278 12.9308C6.96183 12.8373 6.69992 12.723 6.43801 12.5775C6.40659 12.5566 6.37516 12.5463 6.34373 12.5255C6.32743 12.5174 6.31746 12.5093 6.30891 12.5012C6.30414 12.4967 6.29142 12.4886 6.28568 12.4854C6.21882 12.4482 6.16302 12.5211 6.21499 12.5772C6.41689 12.7952 6.77649 13.1099 7.3356 13.3581C7.57131 13.4627 7.66619 13.7639 7.50334 13.9638C7.43459 14.0482 7.37024 14.1269 7.31534 14.1937C7.20484 14.3284 7.03709 14.4053 6.86353 14.3907C5.99459 14.3176 5.34621 14.0394 4.88414 13.7374C4.16187 13.2653 3.92344 12.3691 4.01971 11.5116C4.19969 9.90856 4.66716 8.54423 5.01974 7.69585C5.27928 7.07133 5.72105 6.53224 6.32588 6.22962C7.33742 5.72351 8.17725 5.6799 8.36009 5.67663C8.38359 5.67621 8.40575 5.6868 8.42084 5.7048C8.45892 5.7501 8.43717 5.81977 8.3805 5.83676C7.41642 6.12577 6.72994 6.48512 6.30001 6.75679C6.21083 6.81314 6.26869 6.90504 6.36469 6.86128C7.48564 6.37281 8.37609 6.2377 8.74275 6.20652C8.80567 6.19612 8.858 6.18573 8.92084 6.18573C9.55992 6.10259 10.2828 6.08181 11.0371 6.16495C11.3203 6.19748 11.6094 6.24432 11.9033 6.30835C12.2664 6.38744 12.4 6.12294 12.0503 5.99755C11.9526 5.96255 11.8515 5.92809 11.7469 5.8944C11.6699 5.86958 11.6427 5.77374 11.6963 5.71303C11.7168 5.68973 11.7453 5.67612 11.7763 5.67686C11.9782 5.68165 12.7956 5.7322 13.7793 6.21947C14.3988 6.52631 14.8502 7.07791 15.1144 7.71668C15.4644 8.56265 15.9233 9.90956 16.1034 11.4904C16.2028 12.3612 15.9579 13.2716 15.2212 13.7464C14.756 14.0462 14.1065 14.3208 13.2403 14.3917Z" fill="#292D32"/>
                                        </g>
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_2368_95">
                                            <rect width="20" height="20" fill="white"/>
                                        </clipPath>
                                    </defs>
                                </svg>
                                <a class="booknetic_discord_text booknetic_help_center_item_text" target="_blank" href="https://discord.com/invite/CGaDJHDvDS"><?php echo bkntc__( "Discord community" ) ?></a>
                            </div>
                            <div class="booknetic_contact_support_wrapper booknetic_item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <g clip-path="url(#clip0_2368_105)">
                                        <path d="M8.33342 12.5L5.7707 15.0948C5.41324 15.4567 5.2345 15.6377 5.08087 15.6504C4.94759 15.6615 4.81709 15.6079 4.73005 15.5064C4.62971 15.3894 4.62971 15.135 4.62971 14.6263V13.3264C4.62971 12.87 4.25598 12.5398 3.80442 12.4736V12.4736C2.71153 12.3135 1.85323 11.4552 1.69314 10.3624C1.66675 10.1821 1.66675 9.96712 1.66675 9.53706V5.66669C1.66675 4.26656 1.66675 3.56649 1.93923 3.03171C2.17892 2.56131 2.56137 2.17885 3.03177 1.93917C3.56655 1.66669 4.26662 1.66669 5.66675 1.66669H11.8334C13.2335 1.66669 13.9336 1.66669 14.4684 1.93917C14.9388 2.17885 15.3212 2.56131 15.5609 3.03171C15.8334 3.56649 15.8334 4.26656 15.8334 5.66669V9.16669M15.8334 18.3334L14.0197 17.0724C13.7648 16.8952 13.6373 16.8065 13.4986 16.7437C13.3755 16.6879 13.246 16.6474 13.1131 16.6229C12.9633 16.5953 12.808 16.5953 12.4975 16.5953H11.0001C10.0667 16.5953 9.59995 16.5953 9.24343 16.4136C8.92983 16.2538 8.67486 15.9988 8.51507 15.6852C8.33342 15.3287 8.33342 14.862 8.33342 13.9286V11.8334C8.33342 10.8999 8.33342 10.4332 8.51507 10.0767C8.67486 9.7631 8.92983 9.50813 9.24343 9.34834C9.59995 9.16669 10.0667 9.16669 11.0001 9.16669H15.6667C16.6002 9.16669 17.0669 9.16669 17.4234 9.34834C17.737 9.50813 17.992 9.7631 18.1518 10.0767C18.3334 10.4332 18.3334 10.8999 18.3334 11.8334V14.0953C18.3334 14.8718 18.3334 15.2601 18.2065 15.5664C18.0374 15.9748 17.7129 16.2992 17.3046 16.4684C16.9983 16.5953 16.61 16.5953 15.8334 16.5953V18.3334Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_2368_105">
                                            <rect width="20" height="20" fill="white"/>
                                        </clipPath>
                                    </defs>
                                </svg>
                                <a class="booknetic_contact_support_text booknetic_help_center_item_text" target="_blank" href="https://support.fs-code.com/login"><?php echo bkntc__( "Contact support" ) ?></a>
                            </div>
                        </div>
                        <div class="booknetic_get_informed booknetic_help_center_category">
                            <p class="booknetic_help_center_category_text"><?php echo bkntc__( "Get informed" ) ?></p>
                            <div class="booknetic_documentation_wrapper booknetic_item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M11.6666 1.8913V5.33341C11.6666 5.80012 11.6666 6.03348 11.7574 6.21174C11.8373 6.36854 11.9648 6.49602 12.1216 6.57592C12.2999 6.66675 12.5332 6.66675 12.9999 6.66675H16.442M13.3333 10.8334H6.66659M13.3333 14.1667H6.66659M8.33325 7.50002H6.66659M11.6666 1.66669H7.33325C5.93312 1.66669 5.23306 1.66669 4.69828 1.93917C4.22787 2.17885 3.84542 2.56131 3.60574 3.03171C3.33325 3.56649 3.33325 4.26656 3.33325 5.66669V14.3334C3.33325 15.7335 3.33325 16.4336 3.60574 16.9683C3.84542 17.4387 4.22787 17.8212 4.69828 18.0609C5.23306 18.3334 5.93312 18.3334 7.33325 18.3334H12.6666C14.0667 18.3334 14.7668 18.3334 15.3016 18.0609C15.772 17.8212 16.1544 17.4387 16.3941 16.9683C16.6666 16.4336 16.6666 15.7335 16.6666 14.3334V6.66669L11.6666 1.66669Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <a class="booknetic_documentation_text booknetic_help_center_item_text" target="_blank" href="https://www.booknetic.com/documentation"><?php echo bkntc__( "Documentation" ) ?></a>
                            </div>
                            <div class="booknetic_faq_wrapper booknetic_item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M10 11.25V6.25M7.5 8.75H12.5M5.83333 15V16.9463C5.83333 17.3903 5.83333 17.6123 5.92436 17.7263C6.00352 17.8255 6.12356 17.8832 6.25045 17.8831C6.39636 17.8829 6.56973 17.7442 6.91646 17.4668L8.90434 15.8765C9.31043 15.5517 9.51347 15.3892 9.73957 15.2737C9.94017 15.1712 10.1537 15.0963 10.3743 15.051C10.6231 15 10.8831 15 11.4031 15H13.5C14.9001 15 15.6002 15 16.135 14.7275C16.6054 14.4878 16.9878 14.1054 17.2275 13.635C17.5 13.1002 17.5 12.4001 17.5 11V6.5C17.5 5.09987 17.5 4.3998 17.2275 3.86502C16.9878 3.39462 16.6054 3.01217 16.135 2.77248C15.6002 2.5 14.9001 2.5 13.5 2.5H6.5C5.09987 2.5 4.3998 2.5 3.86502 2.77248C3.39462 3.01217 3.01217 3.39462 2.77248 3.86502C2.5 4.3998 2.5 5.09987 2.5 6.5V11.6667C2.5 12.4416 2.5 12.8291 2.58519 13.147C2.81635 14.0098 3.49022 14.6836 4.35295 14.9148C4.67087 15 5.05836 15 5.83333 15Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <a class="booknetic_faq_text booknetic_help_center_item_text" target="_blank" href="https://www.booknetic.com/faq"><?php echo bkntc__( "FAQ" ) ?></a>
                            </div>
                            <div class="booknetic_blog_wrapper booknetic_item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M9.16667 3.33333H6.5C5.09987 3.33333 4.3998 3.33333 3.86502 3.60582C3.39462 3.8455 3.01217 4.22795 2.77248 4.69836C2.5 5.23314 2.5 5.9332 2.5 7.33333V13.5C2.5 14.9001 2.5 15.6002 2.77248 16.135C3.01217 16.6054 3.39462 16.9878 3.86502 17.2275C4.3998 17.5 5.09987 17.5 6.5 17.5H12.6667C14.0668 17.5 14.7669 17.5 15.3016 17.2275C15.772 16.9878 16.1545 16.6054 16.3942 16.135C16.6667 15.6002 16.6667 14.9001 16.6667 13.5V10.8333M10.8333 14.1667H5.83333M12.5 10.8333H5.83333M16.7678 3.23223C17.7441 4.20854 17.7441 5.79146 16.7678 6.76777C15.7915 7.74408 14.2085 7.74408 13.2322 6.76777C12.2559 5.79146 12.2559 4.20854 13.2322 3.23223C14.2085 2.25592 15.7915 2.25592 16.7678 3.23223Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <a class="booknetic_blog_text booknetic_help_center_item_text" target="_blank" href="https://www.booknetic.com/blog"><?php echo bkntc__( "Blog" ) ?></a>
                            </div>
                        </div>

                        <?php if ( ! Helper::getOption( 'joined_beta', false ) ): ?>
                            <div class="booknetic_join_beta booknetic_help_center_category">
                            <p class="booknetic_help_center_category_text"><?php echo bkntc__( "Join beta" ) ?></p>
                                <div class="booknetic_join_beta_wrapper booknetic_item">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                        <path d="M3.33343 18.1812C3.83558 18.3334 4.51382 18.3334 5.66675 18.3334H14.3334C15.4863 18.3334 16.1646 18.3334 16.6667 18.1812M3.33343 18.1812C3.22577 18.1486 3.12619 18.109 3.03177 18.0609C2.56137 17.8212 2.17892 17.4387 1.93923 16.9683C1.66675 16.4336 1.66675 15.7335 1.66675 14.3334V5.66669C1.66675 4.26656 1.66675 3.56649 1.93923 3.03171C2.17892 2.56131 2.56137 2.17885 3.03177 1.93917C3.56655 1.66669 4.26662 1.66669 5.66675 1.66669H14.3334C15.7335 1.66669 16.4336 1.66669 16.9684 1.93917C17.4388 2.17885 17.8212 2.56131 18.0609 3.03171C18.3334 3.56649 18.3334 4.26656 18.3334 5.66669V14.3334C18.3334 15.7335 18.3334 16.4336 18.0609 16.9683C17.8212 17.4387 17.4388 17.8212 16.9684 18.0609C16.874 18.109 16.7744 18.1486 16.6667 18.1812M3.33343 18.1812C3.33371 17.5068 3.33775 17.1499 3.39746 16.8497C3.66049 15.5274 4.69415 14.4938 6.01645 14.2307C6.33844 14.1667 6.72566 14.1667 7.50008 14.1667H12.5001C13.2745 14.1667 13.6617 14.1667 13.9837 14.2307C15.306 14.4938 16.3397 15.5274 16.6027 16.8497C16.6624 17.1499 16.6665 17.5068 16.6667 18.1812M13.3334 7.91669C13.3334 9.75764 11.841 11.25 10.0001 11.25C8.15913 11.25 6.66675 9.75764 6.66675 7.91669C6.66675 6.07574 8.15913 4.58335 10.0001 4.58335C11.841 4.58335 13.3334 6.07574 13.3334 7.91669Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                <p class="booknetic_join_beta_text booknetic_help_center_item_text"><?php echo bkntc__( "Join beta" ) ?></p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="booknetic_leave_beta booknetic_help_center_category">
                                <p class="booknetic_help_center_category_text"><?php echo bkntc__( "Leave beta" ) ?></p>
                                <div class="booknetic_leave_beta_wrapper booknetic_item">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M2.5 7.50002H13.75C15.8211 7.50002 17.5 9.17895 17.5 11.25C17.5 13.3211 15.8211 15 13.75 15H10M2.5 7.50002L5.83333 4.16669M2.5 7.50002L5.83333 10.8334" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <p class="booknetic_leave_beta_text booknetic_help_center_item_text"><?php echo bkntc__( "Leave beta" ) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
            <div class="booknetic_join_beta_modal" style="display: none">
                <div class="booknetic_join_beta_modal_container">
                    <div class="booknetic_join_beta_modal_top">
                        <div class="booknetic_join_beta_modal_top_left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 16 16" fill="none">
                                <g clip-path="url(#clip0_2320_15084)">
                                    <path d="M2.6666 14.5449C3.06832 14.6666 3.61091 14.6666 4.53325 14.6666H11.4666C12.3889 14.6666 12.9315 14.6666 13.3332 14.5449M2.6666 14.5449C2.58047 14.5188 2.50081 14.4871 2.42527 14.4487C2.04895 14.2569 1.74299 13.951 1.55124 13.5746C1.33325 13.1468 1.33325 12.5868 1.33325 11.4666V4.53331C1.33325 3.41321 1.33325 2.85316 1.55124 2.42533C1.74299 2.04901 2.04895 1.74305 2.42527 1.5513C2.85309 1.33331 3.41315 1.33331 4.53325 1.33331H11.4666C12.5867 1.33331 13.1467 1.33331 13.5746 1.5513C13.9509 1.74305 14.2569 2.04901 14.4486 2.42533C14.6666 2.85316 14.6666 3.41321 14.6666 4.53331V11.4666C14.6666 12.5868 14.6666 13.1468 14.4486 13.5746C14.2569 13.951 13.9509 14.2569 13.5746 14.4487C13.499 14.4871 13.4194 14.5188 13.3332 14.5449M2.6666 14.5449C2.66682 14.0054 2.67006 13.7199 2.71782 13.4797C2.92824 12.4219 3.75517 11.595 4.81301 11.3846C5.07061 11.3333 5.38038 11.3333 5.99992 11.3333H9.99992C10.6195 11.3333 10.9292 11.3333 11.1868 11.3846C12.2447 11.595 13.0716 12.4219 13.282 13.4797C13.3298 13.7199 13.333 14.0054 13.3332 14.5449M10.6666 6.33331C10.6666 7.80607 9.47268 8.99998 7.99992 8.99998C6.52716 8.99998 5.33325 7.80607 5.33325 6.33331C5.33325 4.86055 6.52716 3.66665 7.99992 3.66665C9.47268 3.66665 10.6666 4.86055 10.6666 6.33331Z" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
                                </g>
                                <defs>
                                    <clipPath id="clip0_2320_15084">
                                        <rect width="16" height="16" fill="white"/>
                                    </clipPath>
                                </defs>
                            </svg>
                            <p class="booknetic_join_beta_modal_title"><?php echo bkntc__( "Beta user request confirmation" ) ?></p>
                        </div>
                        <div class="booknetic_join_beta_modal_top_right">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M18 6L6 18M6 6L18 18" stroke="#14151A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="booknetic_join_beta_modal_body">
                        <div class="booknetic_join_beta_modal_body_content">
                            <p><?php echo bkntc__( 'Thank you for joining our Beta program! Your participation is invaluable to us and helps improve our product. 🚀' ) ?></p>
                            <p><span class="booknetic_bold"><?php echo bkntc__( 'Why use the Beta?' ) ?></span></p>
                            <ul>
                                <li><?php echo bkntc__( 'For You: Get early access to new features and enhancements.' ) ?></li>
                                <li><?php echo bkntc__( 'For Us: Your feedback helps us refine and enhance the plugin.' ) ?></li>
                            </ul>
                            <p><?php echo bkntc__( 'As a valued member of our Beta Program, you have the unique opportunity to utilize Booknetic Beta in a second domain exclusively for staging or testing purposes. This benefit is currently available only to our Beta users, empowering you to:' ) ?></p>
                            <ol>
                                <li><?php echo bkntc__( 'Safely Experiment: Test new features and configurations in a controlled staging environment without affecting your main website.' ) ?></li>
                                <li><?php echo bkntc__( 'Provide Feedback: Your insights are crucial. Directly influence the development of Booknetic by sharing your experiences and suggestions.' ) ?></li>
                            </ol>
                            <p>
                                <span class="booknetic_bold"><?php echo bkntc__( 'Important Note:' ) ?></span>
                                <?php echo bkntc__( ' We recommend using the Beta in a staging environment (subdomain) to avoid any oversights. If needed, you can request direct support. We’ll allow Beta usage on your staging subdomain.' ) ?>
                            </p>
                            <p><?php echo bkntc__( 'Remember, the staging environment is a mirror of your production site, allowing you to assess the impact of updates in real-time without any risk to your live operations.' ) ?></p>

                        </div>
                    </div>
                    <div class="booknetic_join_beta_modal_bottom">
                        <div class="booknetic_join_beta_modal_bottom_left">
                            <label class="d-flex align-items-center m-0">
                                <input type="checkbox" class="accept_terms">
                                <p class="m-0 ml-2"><?php echo bkntc__( "Accept outlined" ) ?></>
                            </label>
                        </div>
                        <div class="booknetic_join_beta_modal_bottom_right">
                            <button class="booknetic_cancel"><?php echo bkntc__( 'Cancel' ) ?></button>
                            <button class="booknetic_request_join_beta" disabled><?php echo bkntc__( 'Request' ) ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="booknetic_leave_beta_modal" style="display: none">
                <div class="booknetic_leave_beta_modal_container">
                    <div class="booknetic_leave_beta_modal_top">
                        <div class="booknetic_leave_beta_modal_top_left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 16 16" fill="none">
                                <g clip-path="url(#clip0_2320_15084)">
                                    <path d="M2.6666 14.5449C3.06832 14.6666 3.61091 14.6666 4.53325 14.6666H11.4666C12.3889 14.6666 12.9315 14.6666 13.3332 14.5449M2.6666 14.5449C2.58047 14.5188 2.50081 14.4871 2.42527 14.4487C2.04895 14.2569 1.74299 13.951 1.55124 13.5746C1.33325 13.1468 1.33325 12.5868 1.33325 11.4666V4.53331C1.33325 3.41321 1.33325 2.85316 1.55124 2.42533C1.74299 2.04901 2.04895 1.74305 2.42527 1.5513C2.85309 1.33331 3.41315 1.33331 4.53325 1.33331H11.4666C12.5867 1.33331 13.1467 1.33331 13.5746 1.5513C13.9509 1.74305 14.2569 2.04901 14.4486 2.42533C14.6666 2.85316 14.6666 3.41321 14.6666 4.53331V11.4666C14.6666 12.5868 14.6666 13.1468 14.4486 13.5746C14.2569 13.951 13.9509 14.2569 13.5746 14.4487C13.499 14.4871 13.4194 14.5188 13.3332 14.5449M2.6666 14.5449C2.66682 14.0054 2.67006 13.7199 2.71782 13.4797C2.92824 12.4219 3.75517 11.595 4.81301 11.3846C5.07061 11.3333 5.38038 11.3333 5.99992 11.3333H9.99992C10.6195 11.3333 10.9292 11.3333 11.1868 11.3846C12.2447 11.595 13.0716 12.4219 13.282 13.4797C13.3298 13.7199 13.333 14.0054 13.3332 14.5449M10.6666 6.33331C10.6666 7.80607 9.47268 8.99998 7.99992 8.99998C6.52716 8.99998 5.33325 7.80607 5.33325 6.33331C5.33325 4.86055 6.52716 3.66665 7.99992 3.66665C9.47268 3.66665 10.6666 4.86055 10.6666 6.33331Z" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
                                </g>
                                <defs>
                                    <clipPath id="clip0_2320_15084">
                                        <rect width="16" height="16" fill="white"/>
                                    </clipPath>
                                </defs>
                            </svg>
                            <p class="booknetic_leave_beta_modal_title"><?php echo bkntc__( "Approval to Leave Beta Program" ) ?></p>
                        </div>
                        <div class="booknetic_leave_beta_modal_top_right">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M18 6L6 18M6 6L18 18" stroke="#14151A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="booknetic_leave_beta_modal_body">
                        <div class="booknetic_leave_beta_modal_body_content">
                            <p><?php echo bkntc__( "Are you sure you want to quit the beta program? This action will revert your account to the standard version. Click 'Confirm' to proceed or 'Cancel' to stay in the beta program." ) ?></p>
                        </div>
                    </div>
                    <div class="booknetic_leave_beta_modal_bottom">
                        <div class="booknetic_leave_beta_modal_bottom_right">
                            <button class="booknetic_cancel"><?php echo bkntc__( 'Cancel' ) ?></button>
                            <button class="booknetic_request_leave_beta"><?php echo bkntc__( 'Confirm' ) ?></button>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif (Helper::isTenant()): ?>
            <?php do_action('bkntc_tenant_notification_area' ) ?>
        <?php endif; ?>

        <div class="user_visit_card">
            <div class="circle_image">
                <img src="<?php echo $profileImage?>">
            </div>
            <div class="user_visit_details" data-toggle="dropdown" aria-haspopup="false" aria-expanded="false" onclick="document.getElementsByClassName( 'booknetic_help_center_dropdown' )[ 0 ].style.display = 'none'">
                <span><?php echo bkntc__('Hello %s', [ wp_get_current_user()->display_name ]) ?> <i class="fa fa-angle-down"></i></span>
            </div>
            <div class="dropdown-menu dropdown-menu-right row-actions-area">
                <?php foreach ( MenuUI::getItems( AbstractMenuUI::MENU_TYPE_TOP_RIGHT ) as $menu ): ?>
                    <a href="<?php echo $menu->getLink(); ?>" class="dropdown-item info_action_btn"><i class="<?php echo $menu->getIcon(); ?>"></i> <?php echo $menu->getTitle(); ?></a>
                <?php endforeach; ?>

                <?php if ( Helper::isSaaSVersion() && Permission::isAdministrator() ): ?>
                    <a href="<?php echo admin_url( 'admin.php?page=' . Helper::getSlugName() . '&module=settings&setting=profile' ) ?>"
                       class="dropdown-item">
                        <i class="fa fa-user"></i> <?php echo bkntc__( 'My profile' ) ?>
                    </a>
                    <a href="#" class="dropdown-item share_your_page_btn"><i class="fa fa-share"></i> <?php echo bkntc__('Share your page')?></a>
                <?php endif; ?>

                <hr class="mt-2 mb-2"/>
                <a href="<?php echo wp_logout_url( home_url() ); ?>" class="dropdown-item "><i class="fa fa-sign-out-alt"></i> <?php echo bkntc__('Log out')?></a>
            </div>
        </div>
    </div>
    <?php if(
        Helper::isSaaSVersion() &&
        Helper::getOption('enable_language_switcher', 'off', false) == 'on' &&
        count( Helper::getOption('active_languages', [], false) ) > 1
    ):?>
        <div class="language-chooser-bar d-md-flex d-none">
            <div class="language-chooser" data-toggle="dropdown" aria-haspopup="false" aria-expanded="false">
                <span><?php echo htmlspecialchars(LocalizationService::getLanguageName( Session::get('active_language', get_locale()) ))?></span>
                <i class="fa fa-angle-down"></i>
            </div>
            <div class="dropdown-menu dropdown-menu-right row-actions-area language-switcher-select">
                <?php foreach ( Helper::getOption('active_languages', [], false) AS $active_language ):?>
                    <div data-language-key="<?php echo htmlspecialchars($active_language)?>" class="dropdown-item info_action_btn"><?php echo htmlspecialchars(LocalizationService::getLanguageName( $active_language ))?></div>
                <?php endforeach;?>
            </div>
        </div>
    <?php endif;?>
</div>

<div class="main_wrapper">
    <?php
    if( isset($childViewFile) && file_exists( $childViewFile ) )
        require_once $childViewFile;
    ?>
</div>

<?php if ( ! in_array( 'booknetic_staff', Permission::userInfo()->roles ) ): ?>
    <?php if ( $guidePanelDisabled != 1 ): ?>
        <div class="starting_guide_icon" data-actions="0">
            <img src="<?php echo Helper::icon('starting_guide.svg')?>">
        </div>
    <?php endif; ?>

    <div class="starting_guide_panel">
        <div class="starting_guide_head">
            <div class="starting_guide_title">
                <i class="fa fa-rocket"></i>
                <?php echo bkntc__('Starting guide')?>
                <div class="close_starting_guide close-btn" style="float: right; cursor: pointer"><i class="fa fa-times" style=""></i></div>
            </div>
            <div class="starting_guide_progress_bar">
                <div class="starting_guide_progress_bar_stick"><div class="starting_guide_progress_bar_stick_color"></div></div>
                <div class="starting_guide_progress_bar_text"><span>01</span><span> / 03</span></div>
            </div>
        </div>
        <div class="starting_guide_body">
            <a href="?page=<?php echo Helper::getSlugName() ?>&module=settings&setting=company" class="starting_guide_steps<?php echo ($companyDetailsIsOk ? ' starting_guide_steps_completed' : '')?>"><?php echo bkntc__('Company details')?></a>
            <a href="?page=<?php echo Helper::getSlugName() ?>&module=settings&setting=business_hours" class="starting_guide_steps<?php echo ($businessHoursIsOk ? ' starting_guide_steps_completed' : '')?>"><?php echo bkntc__('Business hours')?></a>
            <?php
            $locationsIsOk   = Location::count() > 0;
            $staffIsOk       = Staff::count() > 0;
            ?>
            <a href="?page=<?php echo Helper::getSlugName() ?>&module=locations" class="starting_guide_steps<?php echo ($locationsIsOk ? ' starting_guide_steps_completed' : '')?>"><?php echo bkntc__('Create location')?></a>
            <a href="?page=<?php echo Helper::getSlugName() ?>&module=staff" class="starting_guide_steps<?php echo ($staffIsOk ? ' starting_guide_steps_completed' : '')?>"><?php echo bkntc__('Create staff')?></a>
            <a href="?page=<?php echo Helper::getSlugName() ?>&module=services" class="starting_guide_steps<?php echo ($servicesIsOk ? ' starting_guide_steps_completed' : '')?>"><?php echo bkntc__('Create service')?></a>
        </div>
    </div>

<?php endif; ?>

</body>
</html>