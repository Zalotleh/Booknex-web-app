<?php

namespace BookneticApp\Backend\Locations\Exceptions;

use Exception;
use function bkntc__;

class LocationNotFoundException extends Exception {
	public function __construct( int $id ) {
		parent::__construct( bkntc__( 'Location not found with ID: %d', [ $id ] ) );
	}
}