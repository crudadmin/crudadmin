<?php

namespace Admin\Facades;

use Illuminate\Support\Facades\Facade;

class EditorMode extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'localization.editormode';
    }
}
