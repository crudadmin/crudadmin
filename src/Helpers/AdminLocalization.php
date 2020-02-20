<?php

namespace Admin\Helpers;

use Gettext;
use Admin\Resources\Helpers\ResourcesGettext;

class AdminLocalization
{
    protected $languages;

    protected $default_localization = null;

    protected $booted = false;

    public function get()
    {
        $this->bootLanguages();

        $segment = $this->default_localization; //add cache or default...

        if ($this->isValid($segment) === false) {
            $language = $this->getDefaultLanguage();
        } else {
            $language = $this->languages->where('slug', $segment)->first();
        }

        return $language;
    }

    public function isValid($segment)
    {
        return $this->languages->where('slug', $segment)->count() == 1;
    }

    public function bootLanguages()
    {
        $this->booted = true;

        if (! ($model = \Admin::getModelByTable('admin_languages'))) {
            return new Collection;
        }

        return $this->languages = $model->all();
    }

    public function getDefaultLanguage()
    {
        return $this->getFirstLanguage();
    }

    public function getFirstLanguage()
    {
        $this->bootLanguages();

        return $this->languages->first();
    }

    public function setLocale($locale)
    {
        $this->default_localization = $locale;

        $this->bootLanguages();

        app()->setLocale($locale);

        $gettextLocale = Gettext::getLocale($locale);

        $language = $this->get();

        //Switch gettext localization
        (new ResourcesGettext)->syncResourceLocales($language);

        Gettext::setLocale($language, $this->getDefaultLanguage());
    }
}
