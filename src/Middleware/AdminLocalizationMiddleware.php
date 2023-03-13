<?php

namespace Admin\Middleware;

use Admin;
use AdminLocalization;
use Localization;
use Closure;

class AdminLocalizationMiddleware
{
    public function handle($request, Closure $next, $guard = null)
    {
        if ( Admin::isAdmin() ) {
            $this->bootAdminLocalization();
        }

        return $next($request);
    }

    public function bootAdminLocalization()
    {
        //Can be boot from automatically
        if ( AdminLocalization::canBootAutomatically() ) {
            AdminLocalization::boot();
        }

        //We need boot localization without admin eloquent
        else {
            AdminLocalization::setLocale(
                AdminLocalization::getLocaleIdentifier()
            );
        }

        //Set website default localization to same as admin language
        if ( Localization::isActive() ) {
            Localization::setDefaultLocale(
                AdminLocalization::getLocaleIdentifier()
            );
        }
    }
}
