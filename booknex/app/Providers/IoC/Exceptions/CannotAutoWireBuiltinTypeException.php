<?php

namespace BookneticApp\Providers\IoC\Exceptions;

use RuntimeException;

class CannotAutoWireBuiltinTypeException extends RuntimeException
{
    public function __construct(string $typeName, string $parameterName)
    {
        parent::__construct("Cannot auto-wire built-in type $typeName for parameter $parameterName");
    }
}