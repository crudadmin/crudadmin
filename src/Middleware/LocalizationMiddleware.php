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
        $segment = $request->segment(1);

        $remove_default = config('admin.localization_remove_default');

        //We want boot admin localization
        if ( Admin::isAdmin() ){
            AdminLocalization::setLocale(config('admin.locale'));

            return $next($request);
        }

        //Checks if is enabled multulanguages
        if (! Localization::isEnabled()) {
            return $next($request);
        }

        if (! Localization::isValidSegment()) {
            $redirect = session()->has('locale') && Localization::isValid(session()->get('locale')) ? session()->get('locale') : Localization::getDefaultLanguage()->slug;

            //Checks if is set default language
            if ($redirect != Localization::getDefaultLanguage()->slug || $remove_default == false) {
                return new RedirectResponse(url($redirect), 301, ['Vary' => 'Accept-Language']);
            }
        } elseif ($segment == Localization::getDefaultLanguage()->slug && $remove_default == true) {
            Localization::save($segment);

            return new RedirectResponse('/', 301, ['Vary' => 'Accept-Language']);
        } elseif (! session()->has('locale') || session()->get('locale') != $segment) {
            Localization::save($segment);
        }

        return $next($request);
    }
}
