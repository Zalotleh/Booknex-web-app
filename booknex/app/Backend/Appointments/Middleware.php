<?php

namespace BookneticApp\Backend\Appointments;

use BookneticApp\Backend\Appointments\Helpers\AppointmentService;

class Middleware
{

	public function handle( $next )
	{
		AppointmentService::cancelUnpaidAppointments();

		return $next();
	}

}