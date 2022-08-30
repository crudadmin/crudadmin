<?php

namespace Admin\Helpers;

use Admin\Eloquent\AdminModel;
use Admin\Helpers\Localization\LocalizationHelper;
use Admin\Helpers\Localization\LocalizationInterface;
use Admin\Models\Language;
use Gettext;
use Illuminate\Support\Collection;
use Admin\Helpers\File;
use Admin;

class Localization extends LocalizationHelper implements LocalizationInterface
{
    const SESSION_LOCALE_KEY = 'locale';

    /*
     * Here will be stored all localized routers
     * They are needed because we need boot them again in different language
     * when user wants to redirect to same route in other language
     */
    public $localizedRouters = [];

    /*
     * Allow for gettext javascript translations use ASSET_PATH.
     * Because other domains cannot receive cookies for translations verification
     */
    public static function crossDomainSupport()
    {
        //We want disable ASSET_PATH for logged administrator
        //All request must be accross same domain
        return admin() ? false : true;
    }

    /**
     * Table of eloquent
     *
     * @return  string
     */
    public function getModel()
    {
        return Admin::getModel('Language') ?: new Language;
    }

    /**
     * Returns locale identifier
     *
     * @return string
     */
    public function getLocaleIdentifier()
    {
        return $this->getLocaleSegmentIdentifier();
    }

    /**
     * Return segment language prefix
     *
     * @return string|null
     */
    public function getLocaleSegmentIdentifier()
    {
        return request()->segment(1);
    }

    /**
     * Localization is disabled in console
     *
     * @return  bool
     */
    public function isActive()
    {
        return \Admin::isEnabledLocalization();
    }

    /**
     * We can boot languages automatically if is not in console mode
     *
     * @return  bool
     */
    public function canBootAutomatically()
    {
        $segment = $this->getLocaleIdentifier();

        return (
            $this->isActive() &&
            app()->runningInConsole() === false
            && \Admin::isAdmin() === false
            && $segment != File::UPLOADS_DIRECTORY
        );
    }

    /**
     * Returns url segment according to prefix language in url
     *
     * @param  int  $id
     * @return  string
     */
    public function segment($id)
    {
        $id = $this->isValidSegment() ? $id + 1 : $id;

        return request()->segment($id);
    }

    /**
     * Save localization
     *
     * @param  string  $lang
     */
    public function saveIntoSession($lang)
    {
        if ( $this->isValid($lang) ) {
            session([
                self::SESSION_LOCALE_KEY => $lang
            ]);

            session()->save();
        }
    }

    public function getFromSession()
    {
        return session(self::SESSION_LOCALE_KEY);
    }

    /**
     * Returns controller parh for
     *
     * @return  string
     */
    public function gettextJsResourcesMethod()
    {
        return 'index';
    }

    /**
     * Check if gettext module is allowed for this localizaiton
     *
     * @return  bool
     */
    public function isGettextAllowed()
    {
        return config('admin.gettext');
    }

    public function addLocalizedRoutes($routes)
    {
        $this->localizedRouters[] = $routes;

        return count($this->localizedRouters) - 1;
    }

    public function getLocalizedRouter($index)
    {
        return $this->localizedRouters[$index];
    }
}
