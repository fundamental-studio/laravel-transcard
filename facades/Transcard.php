<?php

namespace Fundamental\Transcard\Facades;

use Illuminate\Support\Facades\Facade;

class Transcard extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'transcard';
    }
}