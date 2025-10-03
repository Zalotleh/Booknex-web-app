<?php

namespace BookneticApp\Models;

use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;
use BookneticApp\Providers\Translation\Translator;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $image
 * @property-read string $address
 * @property-read string $phone_number
 * @property-read string $notes
 * @property-read string $latitude
 * @property-read string $longitude
 * @property-read int $is_active
 * @property-read int $tenant_id
 */
class Location extends Model
{
	use MultiTenant, Translator;

    protected static $translations = [ 'name', 'address', 'notes' ];

    public static function my() {
        if( Permission::isAdministrator() ) {
            return new self();
        }

        // Permission olan Stafflarin ID-lerini chekib, hemen stafflarin assign olundugu Locationlari gotururuk.
        $allowedLocations = [];
        $myStaffList = Staff::fetchAll();
        foreach( $myStaffList as $staffLocations )
        {
            if( ! empty( $staffLocations->locations ) )
            {
                $allowedLocations = array_merge( $allowedLocations, explode(',', $staffLocations->locations) );
            }
        }

        return Location::where('id', $allowedLocations ?: 0);
    }
}