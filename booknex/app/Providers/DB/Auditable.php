<?php

namespace BookneticApp\Providers\DB;

use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Date;

trait Auditable
{
	public static function booted()
	{
		self::addGlobalScope( 'audit', function ( QueryBuilder $builder, $queryType )
		{
			if ( $queryType == 'insert' ) {
				$builder->created_by = Permission::userId();
				$builder->updated_by = Permission::userId();
				$builder->created_at = Date::epoch();
				$builder->updated_at = Date::epoch();
			} else if ( $queryType == 'update' ) {
				$builder->updated_by = Permission::userId();
				$builder->updated_at = Date::epoch();
			}
		});
	}

	public static function scopeNoAudit( QueryBuilder $builder )
	{
		return $builder->withoutGlobalScope('audit');
	}
}
