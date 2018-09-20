<?php

namespace SolarAbyss\Auth\Facades;

use Illuminate\Support\Facades\Facade;

class Solarize extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'solarize';
    }
}
