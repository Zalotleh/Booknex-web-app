<?php

namespace BookneticApp\Providers\Core;

use BookneticApp\Providers\Helpers\Helper;

class LicenseService
{

    public static function syncLicenseStatus ()
    {
        if ( ! Helper::processRuntimeController( 'license_check', 10 * 60 * 60 ) )
            return;

        $result = DotComApi::safeGet( 'get_notifications' );

        if ( empty( $result['action'] ) )
            return;

        if ( $result['action'] === 'empty' )
        {
            Helper::setOption( 'plugin_alert', '', false );
            Helper::setOption( 'plugin_disabled', '0', false );
        }
        else if ( $result['action'] === 'warning' && ! empty( $result['message'] ) )
        {
            Helper::setOption( 'plugin_alert', $result['message'], false );
            Helper::setOption( 'plugin_disabled', '0', false );
        }
        else if ( $result['action'] === 'disable' )
        {
            if ( ! empty( $result['message'] ) )
                Helper::setOption( 'plugin_alert', $result['message'], false );

            Helper::setOption( 'plugin_disabled', '1', false );
        }
        else if ( $result['action'] === 'error' )
        {
            if ( ! empty( $result['message'] ) )
                Helper::setOption( 'plugin_alert', $result['message'], false );

            Helper::setOption( 'plugin_disabled', '2', false );
        }

        if ( ! empty( $result['remove_license'] ) )
            Helper::deleteOption( 'purchase_code', false );

        Helper::setOption( 'license_last_checked_time', time(), false );
    }

    public static function checkLicense ()
    {
        $alert    = Helper::getOption( 'plugin_alert', '', false );
        $disabled = Helper::getOption( 'plugin_disabled', '0', false );

        if ( $disabled === '1' )
        {
            return false;
        }
        else if ( $disabled === '2' )
        {
            if ( ! empty( $alert ) )
                echo $alert;

            exit();
        }

        if ( ! empty( $alert ) )
        {
            add_action( 'admin_notices', function () use ( $alert )
            {
                echo '<div class="notice notice-error"><p>'.$alert.'</p></div>';
            });
        }

        return true;
    }

}