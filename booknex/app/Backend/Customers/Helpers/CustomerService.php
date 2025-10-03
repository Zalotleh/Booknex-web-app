<?php

namespace BookneticApp\Backend\Customers\Helpers;

use BookneticApp\Models\Appointment;
use BookneticApp\Models\Customer;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;

class CustomerService
{

    // doit: $createWPUser bu silinmelidir. Bu Customer Panel addonuna aid olan bir funksiyadir ve hooklarla Customer panel ozu yoxlamalidi, gerek oldugda wp user yaratmalidir.
    public static function createCustomer( CustomerData $customerData, bool $createWPUser )
    {
        $wpUserId = null;
        $newCustomerPass = null;

        if( $createWPUser )
        {
            $wpUser = get_user_by('email', $customerData->email);

            if ($wpUser) {
                $wpUserId = $wpUser->ID;
                $wpUser->add_role('booknetic_customer');
            } else {
                $newCustomerPass = wp_generate_password( 8, false );
                $wpUserId = self::createWpUser( $customerData, $newCustomerPass );
            }
        }

        Customer::insert( [
            'user_id'		=>	$wpUserId,
            'first_name'	=>	$customerData->first_name,
            'last_name'		=>	$customerData->last_name,
            'phone_number'	=>	$customerData->phone,
            'email'			=>	$customerData->email,
            'created_at'	=>	date('Y-m-d'),
        ] );

        $customerId = DB::lastInsertedId();

        if( $customerId > 0 && $wpUserId > 0 && ! is_null( $newCustomerPass ) )
        {
            do_action( 'bkntc_customer_created', $customerId, $newCustomerPass );
        }

        return $customerId;
    }

    private static function createWpUser( CustomerData $customerData, string $newCustomerPass )
    {
        $customerWPUserId = null;

        if( empty( $customerData->email ) )
            return $customerWPUserId;

        $customerWPUserId = wp_insert_user( [
            'user_login'	=>	$customerData->email,
            'user_email'	=>	$customerData->email,
            'display_name'	=>	$customerData->first_name . ' ' . $customerData->last_name,
            'first_name'	=>	$customerData->first_name,
            'last_name'		=>	$customerData->last_name,
            'role'			=>	'booknetic_customer',
            'user_pass'		=>	$newCustomerPass
        ] );

        /* If error thrown, it means there's already a WordPress user associated with this email */
        if( is_wp_error( $customerWPUserId ) )
        {
            $userInfo = get_user_by( 'email', $customerData->email );

            if( $userInfo && ! Helper::userHasAnyRole( $userInfo, [ 'administrator', 'booknetic_customer', 'booknetic_staff', 'booknetic_saas_tenant' ] ) )
                $userInfo->set_role('booknetic_customer');

            $customerWPUserId = $userInfo ? $userInfo->ID : null;
        }
        else
        {
            /* Save Customer phone number to WP User (WP stores the user's phone number in the billing data) */
            if( !empty( $customerData->phone ) )
                add_user_meta( $customerWPUserId, 'billing_phone', $customerData->phone, true );
        }

        return $customerWPUserId;
    }

    public static function createCustomerIfDoesntExist( CustomerData $customerData, bool $createWPUser )
    {
        $customerId = self::checkIfCustomerExists( $customerData );

        if( empty( $customerId ) )
            $customerId = self::createCustomer( $customerData, $createWPUser );

        return $customerId;
    }

    public static function checkIfCustomerExists( CustomerData $customerData )
    {
        $customerIdentifier = Helper::getOption('customer_identifier', 'email');

        if( $customerIdentifier == 'phone' && ! empty( $customerData->phone ) )
            $checkCustomerExists = Customer::where('phone_number', $customerData->phone)->fetch();
        else if( $customerIdentifier == 'email' && ! empty( $customerData->email ) )
            $checkCustomerExists = Customer::where('email', $customerData->email)->fetch();

        return empty( $checkCustomerExists ) ? null : $checkCustomerExists->id;
    }

    public static function getCustomersOfLoggedInUser()
    {
        return Customer::where( 'user_id', Permission::userId() )->noTenant()->fetchAll();
    }

    /**
     * Front end-den gelen sorguda bezen olur ki, customer_id gonderilir, Deyek ki, mushteri var cari emaide, amma first_name boshdu.
     * Admin goturub silib meselen; Mushteri book edende first name eger required fielddirse bu halda mecbur qalacag burani doldursun;
     * Orani dolduranda da biz mecburug ki, update edek; Eger first_name bosh deilse disabled input olur ve booking zamani editable olmur zaten;
     * Meselen sirf bosh oldugda chixir. Disabled edende ve required field olanda, boshdur deye next stepe kechmir, deyir orani doldur;
     * Ona gore mecbur qalib o fieldi not-disabled etmeli olursan. Bu halda da mecbursan ki, ora yazacagi name`ni DB-e update edesen;
     *
     * @param $customerId
     * @param CustomerData $customerData
     *
     * @return void
     */
    public static function updateOnlyEmptyDataOfCustomer( $customerId, CustomerData $customerData )
    {
        $customerInf = Customer::get( $customerId );

        if( ! $customerInf )
            return;

        $updateData = [];

        if( ! empty( $customerData->email ) && empty( $customerInf->email ) )
            $updateData['email'] = $customerData->email;

        if( ! empty( $customerData->phone ) && empty( $customerInf->phone_number ) )
            $updateData['phone_number'] = $customerData->phone;

        if( ! empty( $customerData->first_name ) && empty( $customerInf->first_name ) )
            $updateData['first_name'] = $customerData->first_name;

        if( ! empty( $customerData->last_name ) && empty( $customerInf->last_name ) )
            $updateData['last_name'] = $customerData->last_name;

        if( ! empty( $updateData ) )
            Customer::where( 'id', $customerId )->update( $updateData );
    }

    /**
     * Customer book edir GMT+10-da, sonra admin girib Dashboarddan Customer uchun appointment yaratmag isteyir;
     * Admin ise GMT+4-dedir zenn edek. Bu halda Customer uchun yaranan appointment timezonesi +4`e dushecekdi;
     * Customere geden notificationlarda da date&timelar +4 ile gedecekdi.
     * Bu funksiya ona destek olur ki, Customerin sonuncu timezonesini axtarir, eger tapsa onu qaytarir
     * ve neticede Customere geden notificationlar hetda admin appointmenti dashboarddan yaratsa bele, duzgum timezone ile olacag.
     *
     * @param $customerId
     *
     * @return string
     */
    public static function findCustomerTimezone ( $customerId ) {
        $appointment = Appointment::where( 'customer_id', $customerId )
                                  ->where( 'client_timezone', '<>', '-' )
                                  ->select( [ 'client_timezone' ] )
                                  ->orderBy('id DESC')
                                  ->fetch();

        $timezone = $appointment->client_timezone ?? '-';

        return apply_filters('bkntc_customer_timezone', $timezone, $customerId);
    }

    /**
     * Customer book edir es_ES localesinde, sonra admin girib Dashboarddan Customer uchun appointment yaratmag isteyir;
     * Admin ise en_EN`dir zenn edek. Bu halda Customer uchun yaranan appointment en_EN kimi dushecekdi;
     * Customere geden notificationlarda da textler en_EN dilinde gedecekdi.
     * Bu funksiya ona destek olur ki, Customerin sonuncu locale`sini axtarir, eger tapsa onu qaytarir
     * ve neticede Customere geden notificationlar hetda admin appointmenti dashboarddan yaratsa bele, duzgum dilde olacag.
     *
     * @param $customerId
     *
     * @return string
     */
    public static function findCustomerLocale ( $customerId ) {
        $appointment = Appointment::where( 'customer_id', $customerId )
                                  ->where( 'locale', '<>', '' )
                                  ->select( [ 'locale' ] )
                                  ->orderBy('id DESC')
                                  ->fetch();

        $locale = $appointment->locale ?? '-';

        return apply_filters('bkntc_customer_locale', $locale, $customerId);
    }

}