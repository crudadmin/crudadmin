<?php
namespace Admin\Facades;

use Illuminate\Support\Facades\Facade;

class Fields extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'fields';
    }
}