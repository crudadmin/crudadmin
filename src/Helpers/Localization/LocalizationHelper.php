<?php

namespace Admin\Helpers\Localization;

use Admin;
use Admin\Eloquent\AdminModel;
use Facades\Admin\Helpers\Localization\JSTranslations;
use Event;
use Gettext;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Support\Collection;

class LocalizationHelper
{
    /*
     * Languages rows
     */
    protected $languages;

    /*
     * Actual localization
     */
    protected $localization = null;

    /**
     * Has been booted?
     *
     * @var  bool
     */
    protected $booted = false;

    /**
     * Default localization, if null, first available will be loaded
     *
     * @var  null
     */
    protected $defaultLocalization = null;

    /**
     *
     *
     * @var  bool
     */
    protected $sessionEnabled = false;

    /**
     * Skip locale change event
     *
     * @var  bool
     */
    static $skipChangeEvent = false;

    /**
     * Boot localization
     */
    public function __construct()
    {
        $this->languages = $this->defaultCollection();

        if ( $this->canBootAutomatically() ) {
            $this->boot();

            //Listen on app()->setLocale event from laravel
            Event::listen(LocaleUpdated::class, function($event){
                if ( self::$skipChangeEvent == true ){
                    self::$skipChangeEvent = false;
                } else {
                    $this->onLaravelLocaleChange($event);
                }
            });
        }
    }

    /**
     * Fire and initialize localization method
     *
     * @return  this
     */
    public function fire()
    {
        return $this;
    }

    /**
     * Boot localization class
     */
    public function boot()
    {
        //Checks if is enabled multi language support for given localization
        if ( $this->isActive() ) {
            //Fetch all languages
            $this->getLanguages();

            //We need assign language first time
            $this->get();
        }

        return $this;
    }

    public function refreshOnSession()
    {
        $this->languages = $this->languages->filter(function($language){
            return $language->isAdminPublished() == true;
        });
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function onLaravelLocaleChange($locale)
    {
        if ( ! $locale->locale ){
            return;
        }

        $this->setLocale($locale->locale, false);
    }

    /**
     * Set default language which will prewrite first language in table
     *
     * @param  string  $prefix
     */
    public function setDefaultLocale($prefix)
    {
        $this->defaultLocalization = $prefix;
    }

    /**
     * Returns defualt language which can be overidden by defaultLocalization property
     *
     * @return  Admin\Eloquent\AdminModel
     */
    public function getDefaultLanguage()
    {
        $this->getLanguages();

        if ($language = $this->getBySlug($this->defaultLocalization)) {
            return $language;
        }

        return $this->getFirstLanguage();
    }

    /**
     * Returns first language in administration
     *
     * @return  Admin\Eloquent\AdminModel|null
     */
    public function getFirstLanguage()
    {
        $this->bootInConsole();

        return $this->all()->first();
    }

    /**
     * Check if given locale is valid
     *
     * @param  string  $locale
     * @return  bool
     */
    public function isValid($locale = null)
    {
        $locale = $locale === null ? $this->getLocaleIdentifier() : $locale;

        if ( ! $locale ){
            return false;
        }

        return $this->all()->where('slug', $locale)->count() == 1;
    }

    /**
     * Check if actual language indentifier is valid
     *
     * @return  bool
     */
    public function isValidSegment()
    {
        return $this->isValid($this->getLocaleSegmentIdentifier());
    }

    /**
     * Get all languages
     *
     * @return  AdminModel
     */
    public function get()
    {
        //Fix for requesting data from console
        $this->bootInConsole();

        //Return by selected localization
        if ( $this->localization ) {
            $language = $this->getBySlug($this->localization);
        }

        //If selected language is not valid, we want return default localization
        else if ($this->isValid() === false) {
            $language = $this->getDefaultLanguage();
        }

        //Return by locale identifier
        else {
            $code = $this->getLocaleIdentifier();

            $language = $this->getBySlug($code);
        }

        //Update app localization
        $this->setLocale(@$language->slug ?: null);

        return $language;
    }

    /**
     * Get language by slug
     *
     * @param  string  $slug
     * @return  Admin\Eloquent\AdminModel|null
     */
    private function getBySlug($slug)
    {
        if ( ! $slug ) {
            return;
        }

        return $this->all()->where('slug', $slug)->first();
    }

    /**
     * Set locale by language
     *
     * @param  string|null  $locale
     * @param  Bool  $updateLaravelLocale
     */
    public function setLocale($locale, $updateLaravelLocale = true)
    {
        //We does not want to set same locale 2 times
        if ($locale == $this->localization) {
            return true;
        }

        $storage = Gettext::getStorage();

        //Switch gettext localization
        if ( $this->isGettextAllowed() ) {
            $language = $this->getBySlug($locale);

            //Try backup default language if no translates are present in given language
            if (!$language || $storage->exists($language->getLocalPoPath()) == false ) {
                $language = $this->getDefaultLanguage();
            }

            //If we need to check some gettext files before location is loaded
            if ( method_exists($this, 'beforeGettextBind') ) {
                $this->beforeGettextBind($language);
            }

            //If language and translations data are present
            if ($language) {
                if ( $language->getLocalPoPath() && $storage->exists($language->getLocalPoPath()) ) {
                    Gettext::setGettextPropertiesModel($language);
                }

                Gettext::setLocale($language->slug, $language->getLocalPoPath());
            }
        }


        $this->localization = $locale;

        $this->setDateLocale($locale);

        self::$skipChangeEvent = true;

        //If laravel locale is set to other than given one
        if ( $updateLaravelLocale === true && app()->getLocale() != $locale ) {
            app()->setLocale($locale);
        }
    }

    public function getLocale()
    {
        return $this->localization;
    }

    /*
     * Automatically set locale for date package if is available in package list
     * https://github.com/jenssegers/date
     * \Jenssegers\Date\Date
     */
    public function setDateLocale($locale)
    {
        if (class_exists(\Jenssegers\Date\Date::class)) {
            \Jenssegers\Date\Date::setLocale($locale);
        }
    }

    /**
     * Returns empty default collection of language
     *
     * @return  Collection
     */
    public function defaultCollection()
    {
        return new Collection;
    }

    /**
     * Get all available languages
     *
     * @param  bool  $console
     * @return  Illuminate\Support\Collection
     */
    public function getLanguages()
    {
        //Return existing languages
        if ( $this->booted === true ) {
            return $this->all();
        }

        $this->booted = true;

        if (! ($model = \Admin::getModelByTable($this->getModel()->getTable()))) {
            return $this->defaultCollection();
        }

        $model->withTemporaryPublished(
            $model->getTable()
        );

        //We want publish models also in administration. Because publishable scope
        //is skipped in admin, we want add it manually.
        if (
            $model->hasGlobalScope('publishable') === false
            && $model->getProperty('publishable') == true
        ){
            $model = $model->withPublished();
        }

        $this->languages = $model->get();

        return $this->all();
    }

    /**
     * Return all languages
     *
     * @return  collection
     */
    public function all()
    {
        return $this->languages;
    }

    /**
     * Is booted from console
     *
     * @return  void
     */
    private function bootInConsole()
    {
        if ( $this->isActive() && app()->runningInConsole() === true ) {
            $this->getLanguages();
        }
    }

    /*
     * Allow for gettext javascript translations use ASSET_PATH.
     * Because other domains cannot receive cookies for translations verification
     */
    public static function crossDomainSupport()
    {
        return true;
    }

    public function prefix($forcedLocale = null)
    {
        $segment = $forcedLocale ?: $this->get()->slug;

        //Boot web multi languages support
        if ( $this->canBootAutomatically() ) {
            //We need redirect all routes to given segment
            if ( $this->isValid($segment) ) {
                //We cannot return default in any situation
                if ( config('admin.localization_remove_default') == true && $segment == $this->getDefaultLanguage()->slug ) {
                    return;
                }

                return $segment;
            }
        }
    }

    public function getJson($locale = null)
    {
        if ( !($locale = $locale ?: $this->get()?->slug) ){
            return '{}';
        }

        return JSTranslations::getJsonTranslations(
            $locale,
            $this->getModel()
        );
    }
}
