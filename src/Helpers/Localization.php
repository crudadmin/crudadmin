<?php

namespace Admin\Helpers;

use Admin\Eloquent\AdminModel;
use Admin\Helpers\Localization\LocalizationHelper;
use Admin\Helpers\Localization\LocalizationInterface;
use Admin\Models\Language;
use Gettext;
use Illuminate\Support\Collection;

class Localization extends LocalizationHelper implements LocalizationInterface
{
    /**
     * Table of eloquent
     *
     * @return  string
     */
    public function getModel()
    {
        return new Language;
    }

    /**
     * Returns locale identifier
     *
     * @return string
     */
    public function getLocaleIdentifier()
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
            && $segment != 'uploads'
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
        session(['locale' => $lang]);
        session()->save();
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
