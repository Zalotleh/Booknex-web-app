<?php

namespace BookneticApp\Providers\IoC;

class ServiceLifetime
{
    const SINGLETON = 'singleton';
    const SCOPED = 'scoped';
    const TRANSIENT = 'transient';
}