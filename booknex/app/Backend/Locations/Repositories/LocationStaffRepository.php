<?php

namespace BookneticApp\Backend\Locations\Repositories;

use BookneticApp\Models\Staff;
use BookneticApp\Providers\DB\DB;

class LocationStaffRepository {
	public function getStaffCount( array $ids ): int {
		return Staff::where( 'locations', 'in', $ids )->count();
	}

	public function deleteLocations( array $ids ) {

		//todo://Bunu QueryBuilder ile etmek lazimdi
		foreach ( $ids as $id ) {
			$statement = DB::DB()->prepare( "UPDATE `" . DB::table( 'staff' ) . "` SET locations=TRIM(BOTH ',' FROM REPLACE(CONCAT(',',`locations`,','),%s,',')) WHERE FIND_IN_SET(%d, `locations`)", [
				",$id,",
				$id
			] );

			DB::DB()->query( $statement );
		}
	}
}