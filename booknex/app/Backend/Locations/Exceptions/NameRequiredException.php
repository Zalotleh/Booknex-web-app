<?php

namespace BookneticApp\Backend\Locations\Exceptions;

use Exception;
use function bkntc__;

class NameRequiredException extends Exception {
	public function __construct() {
		parent::__construct( bkntc__( 'The name field is required!' ) );
	}
}