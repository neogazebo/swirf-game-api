<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class SwirfFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Swirf';
    }
}