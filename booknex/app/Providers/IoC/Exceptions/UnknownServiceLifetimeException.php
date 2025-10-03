<?php

namespace BookneticApp\Providers\IoC\Exceptions;

use RuntimeException;

class UnknownServiceLifetimeException extends RunTimeException
{
    public function __construct(string $lifetime)
    {
        parent::__construct("Unknown service lifetime: $lifetime");
    }
}