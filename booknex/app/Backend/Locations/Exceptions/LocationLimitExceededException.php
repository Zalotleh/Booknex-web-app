<?php

namespace BookneticApp\Backend\Locations\Exceptions;

use Exception;
use function bkntc__;

class LocationLimitExceededException extends Exception {
	public function __construct( int $limit ) {
		parent::__construct( bkntc__( 'You can\'t add more than %d Location. Please upgrade your plan to add more Location.', [ $limit ] ) );
	}
}