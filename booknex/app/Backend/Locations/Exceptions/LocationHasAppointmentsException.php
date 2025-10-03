<?php

namespace BookneticApp\Backend\Locations\Exceptions;

use Exception;
use function bkntc__;

class LocationHasAppointmentsException extends Exception {
	public function __construct() {
		parent::__construct( bkntc__( 'This location has some appointments scheduled. Please remove them first!' ) );
	}
}