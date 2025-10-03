<?php

namespace BookneticApp\Models;

use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;

/**
 * @property-read   int     $id
 * @property-read   int     $service_id
 * @property-read   int     $staff_id
 * @property        string  $timesheet
 * @property-read   int     $tenant_id
 */
class Timesheet extends Model
{
	use MultiTenant;

	protected static $tableName = 'timesheet';

	public static $relations = [
		'service'       => [ Service::class, 'id', 'service_id' ],
		'staff'         => [ Staff::class, 'id', 'staff_id' ]
	];

}
