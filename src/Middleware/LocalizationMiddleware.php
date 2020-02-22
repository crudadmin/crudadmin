<?php

namespace Admin\Middleware;

use Closure;
use Localization;
use AdminLocalization;
use Illuminate\Http\RedirectResponse;
use Admin;

class LocalizationMiddleware
{
    public function handle($request, Closure $next, $guard = null)
    {
        //We want boot admin localization here
        // Because of avaiability of session
        if ( Admin::isAdmin() ) {
            $this->bootAdminLocalization();

            return $next($request);
        }

        //If web localization is enabled
        if ( Localization::canBootAutomatically() ) {
            return $this->webLocalization($request, $next);
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
            AdminLocalization::setLocale(AdminLocalization::getLocaleIdentifier());
        }
    }

    /*
     * Returns web localization
     */
    public function webLocalization($request, Closure $next)
    {
        $segment = Localization::getLocaleIdentifier();

        $removeDefault = config('admin.localization_remove_default');

        if (! Localization::isValidSegment() ) {
            $redirect = session()->has('locale') && Localization::isValid(session()->get('locale'))
                            ? session()->get('locale')
                            : Localization::getDefaultLanguage()->slug;

            //Checks if is set default language
            if ($redirect != Localization::getDefaultLanguage()->slug || $removeDefault == false) {
                return new RedirectResponse(url($redirect), 301, ['Vary' => 'Accept-Language']);
            }
        } elseif ($segment == Localization::getDefaultLanguage()->slug && $removeDefault == true) {
            Localization::save($segment);

            return new RedirectResponse('/', 301, ['Vary' => 'Accept-Language']);
        } elseif (! session()->has('locale') || session()->get('locale') != $segment) {
            Localization::save($segment);
        }

        return $next($request);
    }
}
