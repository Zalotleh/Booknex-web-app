<?php

namespace BookneticApp\Backend\Locations\Exceptions;

use Exception;
use function bkntc__;

class LocationHasStaffMembersException extends Exception {
	public function __construct() {
		parent::__construct( bkntc__( 'There are some staff members currently using this location. Please remove them first!' ) );
	}
}