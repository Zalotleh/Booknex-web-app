<?php

namespace BookneticApp\Models;

use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;

class Holiday extends Model
{
	use MultiTenant;

	public static $relations = [
		'service'       => [ Service::class, 'id', 'service_id' ],
		'staff'         => [ Staff::class, 'id', 'staff_id' ]
	];

}
