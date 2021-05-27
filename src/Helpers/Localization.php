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
            && $segment != File::getUploadsDirectory()
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
    public function save($lang)
    {
        if ( $this->isValid($lang) ) {
            session(['locale' => $lang]);
            session()->save();
        }
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
}
