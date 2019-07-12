<?php

namespace Admin\Facades;

use Illuminate\Support\Facades\Facade;

class SEOFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'seo';
    }
}
