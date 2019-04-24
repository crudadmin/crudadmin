<?php

namespace Gogol\Admin\Facades;

use Illuminate\Support\Facades\Facade;

class ImageCompressor extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'imagecompressor';
    }
}