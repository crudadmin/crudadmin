<?php

namespace Admin\Controllers;

use Admin\Controllers\LocalizationController;
use Illuminate\Http\RedirectResponse;
use Localization;

class LocalizationController extends Controller
{
    public function redirect()
    {
        if ( $default = Localization::getDefaultLanguage()->slug ) {
            Localization::saveIntoSession($default);
        }

        return new RedirectResponse('/', 302, ['Vary' => 'Accept-Language']);
    }
}
