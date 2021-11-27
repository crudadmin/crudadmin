<?php

namespace Admin\Middleware;

use Admin\Helpers\Localization\LocalizationRedirecter;
use Closure;
use Localization;

class LocalizationMiddleware
{
    public function handle($request, Closure $next, $guard = null)
    {
        //If web localization is enabled
        if ( Localization::canBootAutomatically() ) {
            return $this->webLocalizationSupport($request, $next);
        }

        return $next($request);
    }

    /*
     * Returns web localization
     */
    public function webLocalizationSupport($request, Closure $next)
    {
        //We need fetch original language before languages will be removed from array
        //if admin is not logged in.
        $languageBeforeSession = Localization::get();

        Localization::refreshOnSession();

        $redirecter = (new LocalizationRedirecter);

        if ( $redirect = $redirecter->redirectToSessionLanguage() ) {
            return $redirect;
        }

        else if ( $redirect = $redirecter->redirectToOtherLanguage() ) {
            return $redirect;
        }

        else if ( $redirect = $redirecter->isLocalizationUnpublished($languageBeforeSession) ) {
            return $redirect;
        }

        elseif ( $redirecter->hasLocalizationChanged() ) {
            Localization::saveIntoSession(
                Localization::getLocaleIdentifier()
            );
        }

        return $next($request);
    }
}
