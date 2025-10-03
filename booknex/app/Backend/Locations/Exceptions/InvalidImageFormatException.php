<?php

namespace BookneticApp\Backend\Locations\Exceptions;

use Exception;
use function bkntc__;

class InvalidImageFormatException extends Exception {
	public function __construct() {
		parent::__construct( bkntc__( 'Only JPG and PNG images allowed!' ) );
	}
}