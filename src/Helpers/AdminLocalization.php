<?php

namespace Admin\Helpers;

use Admin;
use Admin\Helpers\Localization\LocalizationHelper;
use Admin\Helpers\Localization\LocalizationInterface;
use Admin\Helpers\Localization\ResourcesGettext;
use Admin\Models\AdminLanguage;
use Gettext;
use Illuminate\Support\Collection;

class AdminLocalization extends LocalizationHelper implements LocalizationInterface
{
    /*
     * Allow for gettext javascript translations use ASSET_PATH.
     * Because other domains cannot receive cookies for translations verification
     */
    public static function crossDomainSupport()
    {
        return false;
    }

    /**
     * Table of eloquent
     *
     * @return  string
     */
    public function getModel()
    {
        return Admin::getModel('AdminLanguage') ?: new AdminLanguage;
    }

    /**
     * Returns locale identifier
     *
     * @return string
     */
    public function getLocaleIdentifier()
    {
        //Returns user language
        if ( admin() && admin()->language && $slug = admin()->language->slug ){
            return $slug;
        }

        //Or return default language
        return config('admin.locale', 'sk');
    }

    /**
     * Segment for administration
     * (administration does not have prefix locale segments)
     *
     * @return  null
     */
    public function getLocaleSegmentIdentifier()
    {

    }

    /**
     * Admin localization is enabled only in admin interface
     *
     * @return  bool
     */
    public function isActive()
    {
        return Admin::isEnabledAdminLocalization();
    }

    /**
     * We can boot languages automatically if is admin interface
     *
     * @return  bool
     */
    public function canBootAutomatically()
    {
        return $this->isActive() && Admin::isAdmin() === true;
    }

    /**
     * Returns controller parh for
     *
     * @return  string
     */
    public function gettextJsResourcesMethod()
    {
        return 'adminIndex';
    }

    /**
     * Returns empty default collection of language
     *
     * @return  Collection
     */
    public function defaultCollection()
    {
        if ( $this->isActive() === false ) {
            return collect([
                new AdminLanguage([
                    'slug' => $this->getLocaleIdentifier(),
                ])
            ]);
        }

        return parent::defaultCollection();
    }

    /**
     * Gettext for admin is allowed all the time
     *
     * @return  bool
     */
    public function isGettextAllowed()
    {
        return true;
    }

    public function beforeGettextBind($language)
    {
        //Switch gettext localization
        (new ResourcesGettext)->syncResourceLocales($language);
    }
}
