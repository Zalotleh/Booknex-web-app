<?php

namespace BookneticApp\Providers\Helpers;

use BookneticApp\Models\WorkflowLog;
use BookneticApp\Providers\Core\Permission;
use BookneticSaaS\Models\TenantBilling;

class WorkflowHelper
{
    public static function getUsage( string $driverName ): int
    {
        if( Helper::isSaaSVersion() ) {
            $lastPayment = TenantBilling::where('event_type', 'in', ['subscribed', 'payment_received'])
                                        ->orderBy('id DESC')
                                        ->limit(1)
                                        ->fetch();

            if( ! $lastPayment ) {
                /**
                 * Super admin dashboarddan bu tenanti elave edib demekki ve aktiv subscriptionu yoxdu.
                 */
                $tenantInf = Permission::tenantInf();
                $expirationDateThisMonth = !!$tenantInf ? $tenantInf->expires_in : '';

                /* Eger expire olubsa artig (tebiiki free plana yuvarlanib, amma yenede subscribe oldugu gunu cari il-ayin gunu ile birleshdirib 1 ay chixacayig. */
                if ( Date::epoch() > Date::epoch($expirationDateThisMonth) ) {
                    $expirationDayNumber = Date::format('d', $expirationDateThisMonth);
                    $expirationDateThisMonth = Date::dateSQL( Date::format( 'Y-m-01' ), '+'.($expirationDayNumber-1).' days' );
                }

                $subscriptionPeriodStartDate = Date::dateTimeSQL( $expirationDateThisMonth, '-1 month' );

            } else {
                $subscriptionPeriodStartDate = Date::dateTimeSQL( $lastPayment->created_at );
            }
        } else {
            $subscriptionPeriodStartDate = Date::dateTimeSQL('now', '-1 month');
        }

        return WorkflowLog::where( 'driver', $driverName )
            ->where( 'date_time', '>=', $subscriptionPeriodStartDate )
            ->count();
    }
}