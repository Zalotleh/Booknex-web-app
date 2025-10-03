<?php

namespace BookneticApp\Providers\IoC\Exceptions;

use RuntimeException;

class CannotResolveParameterException extends RuntimeException
{
    public function __construct(string $name)
    {
        parent::__construct(  "Cannot resolve parameter $name without type hint");
    }
}