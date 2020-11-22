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
    /**
     * Table of eloquent
     *
     * @return  string
     */
    public function getModel()
    {
        return new AdminLanguage;
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

    public function isValid($segment)
    {
        return $this->languages->where('slug', $segment)->count() == 1;
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
