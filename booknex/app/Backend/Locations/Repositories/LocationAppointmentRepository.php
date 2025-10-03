<?php

namespace BookneticApp\Backend\Locations\Repositories;

use BookneticApp\Models\Appointment;

class LocationAppointmentRepository {
	public function getAppointmentCount( array $ids ): int {
		return Appointment::where( 'location_id', 'in', $ids )->count();
	}
}