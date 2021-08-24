<?php

namespace Admin\Helpers\Localization;

use Localization;
use Illuminate\Http\RedirectResponse;
use Route;

class LocalizationRedirecter
{
    private function getDefaultLocale()
    {
        return Localization::getDefaultLanguage()->slug;
    }

    public function redirectToSessionLanguage()
    {
        //Redirect to default url, or saved from session if is wrong segment
        //Wrong segment can be also if browser has not any slug,
        //but user has saved other language than default. In this case we want redirect user
        //to the saved language from session.
        if ( !(Localization::isValidSegment() === false && !request()->segment(1)) ) {
            return;
        }

        $removeDefault = config('admin.localization_remove_default');

        $localeFromSession = $localeFromSession = Localization::getFromSession();

        $redirect = $localeFromSession && Localization::isValid($localeFromSession)
                            ? $localeFromSession
                            : $this->getDefaultLocale();

        //Checks if is set default language
        if ($redirect != $this->getDefaultLocale() || $removeDefault == false) {
            return new RedirectResponse(
                url($redirect),
                302,
                ['Vary' => 'Accept-Language']
            );
        }
    }

    public function hasLocalizationChanged()
    {
        $langIdentifier = Localization::getLocaleIdentifier();

        return Localization::getFromSession() != $langIdentifier;
    }

    public function redirectToOtherLanguage()
    {
        if ( !($swithToLocale = request('_locale')) ){
            return;
        }

        $langIdentifier = Localization::get()->slug;

        if ( $swithToLocale == $langIdentifier ){
            return;
        }

        //We want switch locale
        Localization::setLocale($swithToLocale);

        $currentRoute = Route::getCurrentRoute();

        $routerCallback = Localization::getLocalizedRouter(
            $currentRoute->action['localized_router_index']
        );

        Route::middleware($currentRoute->action['middleware'])
            ->namespace($currentRoute->action['namespace'])
            ->group(localizedRoutes($routerCallback, $swithToLocale));

        $query = request()->except(['_locale']);
        $query['_previous_locale'] = $langIdentifier;

        $localizedRoute = action(
            '\\'.$currentRoute->action['controller'],
            $currentRoute->parameters
        ).'/?'.http_build_query($query);

        //We need save language. Othwerise if we would go from /de to /,
        //app will send us back to de.
        Localization::saveIntoSession($swithToLocale);

        return redirect($localizedRoute);
    }

    /**
     * Check if language is also available after session boot. If not, reload page to default language.
     *
     * @param  Language  $originalLanguage
     * @return  bool
     */
    public function isLocalizationUnpublished($originalLanguage)
    {
        if ( !($originalLanguage && Localization::isValidSegment() === false && request()->segment(1)) ) {
            return;
        }

        $defaultLocale = $this->getDefaultLocale();

        if ( $originalLanguage->slug != $defaultLocale ) {
            return redirect($defaultLocale);
        }
    }
}
