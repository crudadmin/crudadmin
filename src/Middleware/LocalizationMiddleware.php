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
            AdminLocalization::setLocale(
                AdminLocalization::getLocaleIdentifier()
            );
        }
    }

    /*
     * Returns web localization
     */
    public function webLocalization($request, Closure $next)
    {
        $segment = Localization::getLocaleIdentifier();

        $defaultSlug = Localization::getDefaultLanguage()->slug;

        $removeDefault = config('admin.localization_remove_default');

        //Redirect to default url, or saved from session if is wrong segment
        //Wrong segment can be also if browser has not any slug,
        //but user has saved other language than default. In this case we want redirect user
        //to the saved language from session.
        if ( Localization::isValidSegment() === false && !request()->segment(1) ) {
            $redirect = session()->has('locale') && Localization::isValid(session('locale'))
                                ? session('locale')
                                : $defaultSlug;

            //Checks if is set default language
            if ($redirect != $defaultSlug || $removeDefault == false) {
                return new RedirectResponse(url($redirect), 302, ['Vary' => 'Accept-Language']);
            }
        }

        //If user has same segment as default url, we want redirect user to /
        elseif ($segment == $defaultSlug && $removeDefault == true) {
            Localization::save($segment);

            return new RedirectResponse('/', 302, ['Vary' => 'Accept-Language']);
        }

        //Save segment
        elseif (! session()->has('locale') || session('locale') != $segment) {
            Localization::save($segment);
        }

        return $next($request);
    }
}
