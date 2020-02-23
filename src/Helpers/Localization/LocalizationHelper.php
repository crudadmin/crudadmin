<?php

namespace Admin\Helpers\Localization;

use Admin\Eloquent\AdminModel;
use Illuminate\Support\Collection;
use Gettext;

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
     * Boot localization
     */
    public function __construct()
    {
        $this->languages = $this->defaultCollection();

        if ( $this->canBootAutomatically() ) {
            $this->boot();
        }
    }

    /**
     * Boot localization class
     *
     * @return
     */
    public function boot()
    {
        //Checks if is enabled multi language support for given localization
        if ( $this->isActive() === false ) {
            return false;
        }

        //Fetch all languages
        $this->getLanguages();

        return $this->get()->slug;
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

        return $this->languages->first();
    }

    /**
     * Check if given segment is valid
     *
     * @param  string  $segment
     * @return  bool
     */
    public function isValid($segment)
    {
        return $this->languages->where('slug', $segment)->count() == 1;
    }

    /**
     * Check if actual language indentifier is valid
     *
     * @return  bool
     */
    public function isValidSegment()
    {
        return $this->isValid($this->getLocaleIdentifier());
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

        $segment = $this->getLocaleIdentifier();

        if ($this->isValidSegment() === false) {
            $language = $this->getDefaultLanguage();
        } else {
            $language = $this->getBySlug($segment);
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

        return $this->languages->where('slug', $slug)->first();
    }

    /**
     * Set locale by language
     *
     * @param  string|null  $locale
     */
    public function setLocale($locale)
    {
        //We does not want to set same locale 2 times
        if ($locale == $this->localization) {
            return true;
        }

        app()->setLocale($locale);

        //Switch gettext localization
        if ( $this->isGettextAllowed() ) {
            $language = $this->getBySlug($locale);

            //Try backup default language if no translates are present in given language
            if (!$language || !$language->getPoPath()) {
                $language = $this->getDefaultLanguage();
            }

            //If we need to check some gettext files before location is loaded
            if ( method_exists($this, 'beforeGettextBind') ) {
                $this->beforeGettextBind($language);
            }

            //If language and translations data are present
            if ($language && $language->getPoPath() && $language->getPoPath()->exists()) {
                Gettext::setGettextPropertiesModel($language);
                Gettext::setLocale($language->slug, $language->getPoPath());
            }
        }

        $this->setDateLocale($locale);

        $this->localization = $locale;
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
            return $this->languages;
        }

        $this->booted = true;

        if (! ($model = \Admin::getModelByTable($this->getModel()->getTable()))) {
            return $this->defaultCollection();
        }

        return $this->languages = $model->all();
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
}
