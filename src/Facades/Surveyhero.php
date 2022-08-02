<?php

namespace Statikbe\Surveyhero\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Statikbe\Surveyhero\Surveyhero
 */
class Surveyhero extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Statikbe\Surveyhero\Surveyhero::class;
    }
}
