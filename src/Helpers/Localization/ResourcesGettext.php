<?php

namespace Admin\Helpers\Localization;

use Cache;
use Facades\Admin\Helpers\Localization\JSTranslations;
use Gettext;
use Gettext\Translations;

class ResourcesGettext
{
    public function getVendorPoPath($slug)
    {
        $locale = Gettext::getLocale($slug);

        return crudadmin_resources_path('lang/gettext/'.$locale.'.po');
    }

    public function getCacheKey($locale)
    {
        return 'admin.translates.syncLocale.'.$locale;
    }

    /**
     * Sync language files from vendor folder into application translations in storage/langs/admin_gettext...
     *
     * @param  AdminModel  $language
     * @return  void
     */
    public function syncResourceLocales($language)
    {
        Gettext::setGettextPropertiesModel($language);

        $locale = $language->locale;

        $storage = Gettext::getStorage();

        $resourcesPoPath = $this->getVendorPoPath($language->slug);

        //If resource translates for this language does not exists
        if ( !file_exists($resourcesPoPath) ) {
            return;
        }

        $resourcesLanguageTimestamp = filemtime($resourcesPoPath);

        //If there is no change in resource files
        if ( Cache::get($this->getCacheKey($locale)) == $resourcesLanguageTimestamp ) {
            return;
        }

        //Cache
        Cache::set($this->getCacheKey($locale), $resourcesLanguageTimestamp);

        $localPoPath = $language->localPoPath;

        if ( $storage->exists($localPoPath) ) {
            $translations = Translations::fromPoFile($language->localPoBasepath);
        } else {
            $translations = new Translations;
        }

        $resourceTranslations = Translations::fromPoFile($resourcesPoPath);

        $translations->mergeWith($resourceTranslations);

        JSTranslations::rebuildGettextFiles($language, $translations);
    }
}