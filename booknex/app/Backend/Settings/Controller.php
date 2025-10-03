<?php

namespace BookneticApp\Backend\Settings;

use BookneticApp\Providers\UI\SettingsMenuUI;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Helper;

class Controller extends \BookneticApp\Providers\Core\Controller
{
	public function index()
	{
		Capabilities::must( 'settings' );

        SettingsMenuUI::get( 'general_settings' )
            ->setTitle( bkntc__( 'General settings' ) )
            ->setDescription( bkntc__( 'You can customize general settings about booking from here' ) )
            ->setIcon( Helper::icon( 'general-settings.svg', 'Settings' ) )
            ->requireSubItems()
            ->setPriority( 1 );

        if ( Capabilities::userCan( 'settings_general' ) && Capabilities::tenantCan( 'settings_general' ) )
        {
            SettingsMenuUI::get('general_settings')
                ->subItem( 'general_settings' )
                ->setTitle( bkntc__('General') )
                ->setPriority( 1 );
        }

        SettingsMenuUI::get( 'frontend' )
                  ->setTitle( bkntc__( 'Front-end panels' ) )
                  ->setDescription( bkntc__( 'You can customize booking and customer panel and change labels from here' ) )
                  ->setIcon( Helper::icon( 'booking-steps-settings.svg', 'Settings' ) )
                  ->requireSubItems()
                  ->setPriority( 2 );

        if ( Capabilities::userCan( 'settings_booking_panel_steps' ) && Capabilities::tenantCan( 'settings_booking_panel_steps' ) )
        {
            SettingsMenuUI::get( 'frontend' )
                      ->subItem( 'booking_panel_steps_settings' )
                      ->setTitle( bkntc__( 'Booking Steps' ) )
                      ->setPriority( 1 );
        }

        if ( Capabilities::userCan( 'settings_booking_panel_labels' ) && Capabilities::tenantCan( 'settings_booking_panel_labels' ) )
        {
            SettingsMenuUI::get( 'frontend' )
                      ->subItem( 'booking_panel_labels_settings' )
                      ->setTitle( bkntc__( 'Labels' ) )
                      ->setPriority( 2 );
        }

        if ( Capabilities::userCan( "settings_payments" ) && Capabilities::tenantCan( 'settings_payments' ) )
        {
            SettingsMenuUI::get( 'payment_settings' )
                ->setTitle( bkntc__('Payment settings') )
                ->setDescription( bkntc__('Currency, price format , general settings about payment , payment methods and so on') )
                ->setIcon( Helper::icon('payments-settings.svg', 'Settings') )
                ->setPriority( 3 );
        }

        if ( Capabilities::tenantCan( 'settings_payments' ) && Capabilities::userCan( 'settings_payments' ) )
        {
            SettingsMenuUI::get( 'payment_settings' )
                      ->subItem( 'payments_settings' )
                      ->setTitle( bkntc__( 'General' ) )
                      ->setPriority( 1 );
        }

        if ( Capabilities::tenantCan( 'settings_payments' ) &&  Capabilities::tenantCan( 'settings_payment_gateways' ) && Capabilities::userCan( 'settings_payment_gateways' ) )
        {
            SettingsMenuUI::get( 'payment_settings' )
                      ->subItem( 'payment_gateways_settings' )
                      ->setTitle( bkntc__( 'Payment methods' ) )
                      ->setPriority( 2 );
        }

        if ( Capabilities::userCan( 'settings_company' ) && Capabilities::tenantCan( 'settings_company' ) )
        {
            SettingsMenuUI::get( 'company_settings' )
                      ->setTitle( bkntc__( 'Company details' ) )
                      ->setDescription( bkntc__( 'Enter your company name, logo, address, phone number, website from here' ) )
                      ->setIcon( Helper::icon( 'company-settings.svg', 'Settings' ) )
                      ->setPriority( 4 );
        }

        if ( Capabilities::userCan( 'settings_business_hours' ) && Capabilities::tenantCan( 'settings_business_hours' ) )
        {
            SettingsMenuUI::get( 'business_hours_settings' )
                      ->setTitle( bkntc__( 'Business Hours' ) )
                      ->setDescription( bkntc__( 'You will be able to co-ordinate your company\'s overall work schedule' ) )
                      ->setIcon( Helper::icon( 'business-hours-settings.svg', 'Settings' ) )
                      ->setPriority( 5 );
        }

        if ( Capabilities::userCan( 'settings_holidays' ) && Capabilities::tenantCan( 'settings_holidays' ) )
        {
            SettingsMenuUI::get( 'holidays_settings' )
                      ->setTitle( bkntc__( 'Holidays' ) )
                      ->setDescription( bkntc__( 'You can select dates that you are unavailable or on holiday' ) )
                      ->setIcon( Helper::icon( 'holidays-settings.svg', 'Settings' ) )
                      ->setPriority( 6 );
        }

        SettingsMenuUI::get( 'integrations' )
                  ->setTitle( bkntc__('Integrations settings') )
                  ->setDescription( bkntc__('You can change settings for integrated services from here.') )
                  ->setIcon( Helper::icon('integrations-settings.svg', 'Settings') )
                  ->setPriority( 8 )
                  ->requireSubItems();

		if ( ! Helper::isSaaSVersion() ) {
            if ( Capabilities::userCan( "settings_integrations_google_login" ) ) {
                SettingsMenuUI::get( 'integrations' )
                    ->subItem( 'integrations_facebook_api_settings' )
                    ->setTitle( bkntc__( 'Continue with Facebook' ) )
                    ->setPriority( 1 );
            }

            if ( Capabilities::userCan( "settings_integrations_facebook_api" ) ) {
                SettingsMenuUI::get( 'integrations' )
                    ->subItem( 'integrations_google_login_settings' )
                    ->setTitle( bkntc__( 'Continue with Google' ) )
                    ->setPriority( 2 );
            }

            if ( Capabilities::userCan( "settings_backup" ) ) {
                SettingsMenuUI::get( 'backup_settings' )
                    ->setTitle( bkntc__('Export & Import data') )
                    ->setDescription( bkntc__('You can export all Booknetic data and import from here.') )
                    ->setIcon( Helper::icon('backup-settings.svg', 'Settings') )
                    ->setPriority( 9 );
            }

            if ( Capabilities::userCan( "page_settings" ) && Capabilities::tenantCan( 'page_settings' ) ) {
                SettingsMenuUI::get( 'frontend' )
                    ->subItem( 'page_settings' )
                    ->setTitle( bkntc__( 'Pages' ) )
                    ->setPriority( 3 );
            }
		} else {
            if ( Capabilities::tenantCan( 'settings_profile_settings' ) && Helper::isTenant() ) {
                SettingsMenuUI::get( 'profile_settings' )
                    ->setTitle( bkntc__( 'Profile Settings' ) )
                    ->setDescription( bkntc__( 'Update your profile information' ) )
                    ->setIcon( Helper::icon( 'profile-settings.svg', 'Settings' ) )
                    ->setPriority( 4 );

                SettingsMenuUI::get( 'profile_settings' )
                    ->subItem('profile_details')
                    ->setTitle( bkntc__( 'Profile Details' ) )
                    ->setPriority( 4 );

                SettingsMenuUI::get( 'profile_settings' )
                    ->subItem('change_password')
                    ->setTitle( bkntc__( 'Change Password' ) )
                    ->setPriority( 4 );

                SettingsMenuUI::get( 'profile_settings' )
                    ->subItem('delete_account')
                    ->setTitle( bkntc__( 'Delete Account' ) )
                    ->setPriority( 4 );
            }
        }

        add_filter('bkntc_localization', function ($localization) {
            $localization['not_allowed_image_type'] = bkntc__('Invalid file type. Only JPG, JPEG, and PNG are allowed.');

            return $localization;
        });

		$this->view( 'index', [
			'menu' => SettingsMenuUI::getItems()
		] );
	}

}
