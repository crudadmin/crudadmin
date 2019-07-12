<?php

namespace Admin\Facades;

use Illuminate\Support\Facades\Facade;

class Localization extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'localization';
    }
}
