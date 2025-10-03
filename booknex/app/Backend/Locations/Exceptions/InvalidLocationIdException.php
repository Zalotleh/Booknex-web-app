<?php

namespace BookneticApp\Backend\Locations\Exceptions;

use Exception;
use function bkntc__;

class InvalidLocationIdException extends Exception {
	public function __construct() {
		parent::__construct( bkntc__( 'Invalid location ID!' ) );
	}
}