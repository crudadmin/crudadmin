<?php

namespace Admin\Facades;

use Illuminate\Support\Facades\Facade;

class AdminTree extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'admintree';
    }
}
