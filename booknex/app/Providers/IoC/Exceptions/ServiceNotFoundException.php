<?php

namespace BookneticApp\Providers\IoC\Exceptions;

use RuntimeException;

class ServiceNotFoundException extends RuntimeException
{
    public function __construct( $id )
    {
        parent::__construct("Service $id not found in container and class does not exist");
    }
}