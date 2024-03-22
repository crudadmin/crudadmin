<?php

namespace Admin\Eloquent\Concerns;

use Admin;
use Localization;

trait HasEntryLocales
{
    public function addLocale()
    {
        if ( Admin::isEnabledLocalization() && !$this->language_id ) {
            $this->language_id = Localization::get()->getKey();
        }
    }

    public function saveLocale()
    {
        if ( Admin::isEnabledLocalization() ){
            $localeId = Localization::get()?->getKey();

            if ( $localeId && $this->language_id != $localeId ){
                $this->update([
                    'language_id' => $localeId,
                ]);
            }
        }

        return $this;
    }

    public function getLocale()
    {
        return Localization::boot()->all()->firstWhere('id', $this->language_id) ?: Localization::getFirstLanguage();
    }

    /**
     * Set user localization
     *
     * @param  string  $locale
     */
    public function setLocale($locale = null)
    {
        $locale = $locale ?: $this->getLocale()?->slug;

        Localization::setLocale($locale);

        return $this;
    }

    /**
     * Run the callback with the given locale.
     *
     * @param  string  $locale
     * @param  \Closure  $callback
     * @return mixed
     */
    public function withLocale($callback, $locale = null)
    {
        $locale = $locale ?: $this->getLocale()?->slug;

        if (! $locale) {
            return $callback();
        }

        $original = app()->getLocale();

        //Make sure localization is booted
        Localization::boot();

        try {
            //We want set web locales
            Localization::setLocale($locale);

            return $callback();
        } finally {
            //We need set locales like that,
            //becuase if admin locales are present
            //we want set them back..
            app()->setLocale($original);
        }
    }
}