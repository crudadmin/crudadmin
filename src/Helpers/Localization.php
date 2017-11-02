<?php
namespace Gogol\Admin\Helpers;

use Gogol\Admin\Models\Language;
use Illuminate\Support\Collection;
use Gettext;

class Localization
{
    protected $languages;

    protected $localization = null;

    protected $default_localization = null;

    public function __construct()
    {
        $this->languages = new Collection;

        //Checks if is enabled multi language support
        if ( ! \Admin::isEnabledMultiLanguages() || app()->runningInConsole() == true )
            return false;

        $this->bootLanguages();
    }

    public function bootLanguages()
    {
        return $this->languages = \Admin::getModelByTable('languages')->all();
    }

    public function boot()
    {
        //Checks if is enabled multi language support
        if ( ! $this->isEnabled() )
            return false;

        if ( ! $this->isValidSegment() )
        {
            //Update app localization for default language
            $this->setLocale( $this->getDefaultLanguage()->slug );

            return false;
        }

        return $this->get()->slug;
    }

    public function setLocale($locale)
    {
        if ( $locale == $this->localization )
            return true;

        app()->setLocale($locale);

        //Switch gettext localization
        if ( config('admin.gettext') === true )
        {
            Gettext::setLocale($locale);
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
        if ( class_exists( \Jenssegers\Date\Date::class ) )
        {
            \Jenssegers\Date\Date::setLocale( $locale );
        }
    }

    public function isEnabled()
    {
        return \Admin::isEnabledMultiLanguages() && app()->runningInConsole() == false && request()->segment(1) != 'admin';
    }

    public function getLanguages($console = false)
    {
        if ( $console === true && count($this->languages) == 0 )
        {
            return $this->bootLanguages();
        }

        return $this->languages;
    }

    public function getDefaultLanguage()
    {
        if ( $this->default_localization && $language = $this->languages->where('slug', $this->default_localization)->first() )
        {
            return $language;
        }

        return $this->languages->first();
    }

    public function setDefaultLocale($prefix)
    {
        $this->default_localization = $prefix;
    }

    public function isValid($segment)
    {
        return $this->languages->where('slug', $segment)->count() == 1;
    }

    public function isValidSegment()
    {
        return $this->isValid( request()->segment(1) );
    }

    public function get()
    {
        $segment = request()->segment(1);

        if ( ! $this->isValidSegment() )
            $language = $this->getDefaultLanguage();
        else
            $language = $this->languages->where('slug', $segment)->first();

        //Update app localization
        $this->setLocale( $language->slug );

        return $language;
    }

    public function segment($id)
    {
        $id = $this->isValidSegment() ? $id + 1 : $id;

        return request()->segment( $id );
    }

    public function save($lang)
    {
        session(['locale' => $lang]);
        session()->save();
    }

    public function createLangSlug($slug)
    {
        if ( $slug == 'en' )
            return 'en_US';

        return $slug . '_' . strtoupper($slug);
    }
}
?>