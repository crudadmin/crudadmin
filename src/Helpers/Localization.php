<?php
namespace Admin\Helpers;

use Admin\Models\Language;
use Illuminate\Support\Collection;
use Gettext;

class Localization
{
    protected $languages;

    protected $localization = null;

    protected $default_localization = null;

    protected $booted = false;

    public function __construct()
    {
        $this->languages = new Collection;

        //Checks if is enabled multi language support
        if ( ! \Admin::isEnabledLocalization() || app()->runningInConsole() == true )
            return false;

        $this->bootLanguages();
    }

    public function bootLanguages()
    {
        $this->booted = true;

        if ( !($model = \Admin::getModelByTable('languages')) )
            return new Collection;

        return $this->languages = $model->all();
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
        $segment = request()->segment(1);

        return \Admin::isEnabledLocalization()
            && app()->runningInConsole() == false
            && $segment != 'admin'
            && $segment != 'uploads';
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
        $this->checkForConsoleBoot();

        if ( $this->default_localization && $language = $this->languages->where('slug', $this->default_localization)->first() )
        {
            return $language;
        }

        return $this->getFirstLanguage();
    }

    public function setDefaultLocale($prefix)
    {
        $this->default_localization = $prefix;
    }

    public function getFirstLanguage()
    {
        $this->checkForConsoleBoot();

        return $this->languages->first();
    }

    public function isValid($segment)
    {
        return $this->languages->where('slug', $segment)->count() == 1;
    }

    public function isValidSegment()
    {
        return $this->isValid( request()->segment(1) );
    }

    private function checkForConsoleBoot()
    {
        if (
            $this->booted === false
            && \Admin::isEnabledLocalization() === true
            && app()->runningInConsole() === true
        ) {
            $this->bootLanguages();
        }
    }

    public function get()
    {
        //Fix for requesting data from console
        $this->checkForConsoleBoot();

        $segment = request()->segment(1);

        if ( $this->isValidSegment() === false )
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