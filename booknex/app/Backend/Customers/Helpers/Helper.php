<?php

namespace BookneticApp\Backend\Customers\Helpers;

class Helper
{
    public static function canBeCustomer( $email ): bool
    {
        return !
        (
            ( $wp_user = get_user_by( 'email', $email ) )
            &&
            (
                in_array( 'booknetic_staff', $wp_user->roles )
                ||
                in_array( 'booknetic_saas_tenant', $wp_user->roles )
                ||
                in_array( 'administrator', $wp_user->roles )
            )
        );
    }
}