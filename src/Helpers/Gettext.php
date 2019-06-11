<?php
namespace Gogol\Admin\Helpers;

use \App\Core\Models\Language;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Gogol\Admin\Helpers\File;
use Gettext\Extractors\PhpCode;
use Gettext\Generators\Json;
use Gettext\Translations;
use Localization;
use Storage;
use Cache;

class Gettext
{
    protected $filesystem;

    protected $basepath = 'storage/app/lang';

    protected $supported_codes = ['fr_FR', 'sr_RS', 'es_ES', 'kl_GL', 'ts_ZA', 'ar_IQ', 'ti_ET', 'tt_RU', 'de_DE', 'es_CL','sw_TZ', 'lv_LV', 'cs_CZ', 'cz' => 'cs_CZ', 'ur_IN', 'id_ID', 'ar_QA', 'ks_IN', 'ar_YE', 'lg_UG', 'fo_FO', 'ka_GE', 'aa_DJ', 'es_UY', 'en_US', 'sq_MK', 'os_RU', 'so_DJ', 'ja_JP', 'ar_KW', 'ca_ES', 'gl_ES', 'eo_US', 'nb_NO', 'af_ZA', 'nl_BE', 'pa_IN', 'es_US', 'sd_IN', 'li_BE', 'pt_PT', 'nl_NL', 'is_IS', 'br_FR', 'sq_AL', 'so_ET', 'es_VE', 'gu_IN', 'ar_DZ', 'sc_IT', 'tt_RU', 'ca_FR', 'aa_ER', 'ar_SY', 'ff_SN', 'kk_KZ', 'dz_BT', 'bo_CN', 'mg_MG', 'zh_CN', 'ar_LY', 'th_TH', 'sv_FI', 'fi_FI', 'ar_IN', 'ta_IN', 'br_FR', 'kn_IN', 'eu_FR', 'az_AZ', 'zu_ZA', 'ar_EG', 'st_ZA', 'so_KE', 'hy_AM', 'bn_BD', 'rw_RW', 'my_MM', 'es_CU', 'ar_OM', 'he_IL', 'pl_PL', 'mt_MT', 'ht_HT', 'bg_BG', 'mn_MN', 'ps_AF', 'ru_UA', 'cv_RU', 'ms_MY', 'ar_BH', 'oc_FR', 'te_IN', 'wa_BE', 'nr_ZA', 'ar_JO', 'iu_CA', 'eu_ES', 'ko_KR', 'et_EE', 'tg_TJ', 'uk_UA', 've_ZA', 'yo_NG', 'wo_SN', 'mi_NZ', 'ga_IE', 'ku_TR', 'fy_DE', 'tl_PH', 'ne_NP', 'fy_NL', 'ga_IE', 'se_NO', 'bs_BA', 'aa_ET', 'es_SV', 'ca_ES', 'tn_ZA', 'xh_ZA', 'ca_IT', 'es_CO', 'lb_LU', 'tr_TR', 'sa_IN', 'pa_PK', 'POSIX', 'nn_NO', 'el_CY', 'uz_UZ', 'es_BO', 'es_EC', 'bo_IN', 'sv_SE', 'es_CR', 'as_IN', 'lo_LA', 'zh_SG', 'sl_SI', 'ug_CN', 'gv_GB', 'hr_HR', 'yi_US', 'cy_GB', 'dv_MV', 'or_IN', 'es_PA', 'pt_PT', 'bn_IN', 'ru_RU', 'be_BY', 'es_HN', 'zh_TW', 'an_ES', 'eu_FR', 'ik_CA', 'ti_ER', 'ar_TN', 'ur_PK', 'om_KE', 'fi_FI', 'ar_AE', 'it_IT', 'sd_PK', 'es_AR', 'gl_ES', 'ml_IN', 'sd_IN', 'fa_IR', 'es_DO', 'es_GT', 'km_KH', 'ig_NG', 'wa_BE', 'es_NI', 'pt_BR', 'ro_RO', 'el_GR', 'be_BY', 'gd_GB', 'ha_NG', 'vi_VN', 'nl_AW', 'ky_KG', 'ks_IN', 'tr_CY', 'eu_ES', 'li_NL', 'nl_NL', 'mr_IN', 'uz_UZ', 'om_ET', 'es_PR', 'kw_GB', 'zh_HK', 'ug_CN', 'nl_BE', 'ar_SA', 'sk_SK', 'hu_HU', 'sv_FI', 'ta_LK', 'hi_IN', 'it_CH', 'es_PY', 'ar_MA', 'lt_LT', 'so_SO', 'am_ET', 'da_DK', 'ca_ES', 'mk_MK', 'sw_KE', 'iw_IL', 'es_MX', 'si_LK', 'ca_AD', 'ss_ZA', 'tk_TM', 'ar_SD', 'it_IT', 'es_PE', 'ar_LB', 'el_GR', 'aa_ER'];

    private $modification_timestamp = null;

    public function __construct()
    {
        $this->filesystem = new Filesystem;
    }

    public function getBasePath($path = null)
    {
        return base_path( $this->basepath . '/' . $path );
    }

    public function getGettextPath($path = null)
    {
        return $this->getBasePath( 'gettext/' . $path );
    }

    public function getLocalePath($locale, $file = null)
    {
        return $this->getGettextPath( $locale . '/LC_MESSAGES/' . $file);
    }

    public function getSourcePaths($blade_files = true)
    {
        $paths = config('admin.gettext_source_paths', []);

        return $paths;
    }

    public function getSupportedCodes()
    {
        $codes = config('admin.gettext_supported_codes');
        $codes = isset($codes) ? $codes : [];

        return array_merge($codes, $this->supported_codes);
    }

    public function getLocale($locale)
    {
        if ( strlen($locale) == 5 )
            return $locale;

        $locales = $this->getSupportedCodes();

        if ( array_key_exists($locale, $locales) )
        {
            return $locales[ $locale ];
        }

        foreach ($locales as $code)
        {
            $part = explode('_', $code);

            if ( $part[0] == $locale )
                return $code;
        }

        return false;
    }

    public function setLocale($locale)
    {
        if ( ! ($language = \Localization::getLanguages()->where('slug', $locale)->first()))
        {
            return false;
        }

        if ( $language->poedit_mo == null )
            $language = \Localization::getDefaultLanguage();

        if ( $language->poedit_mo == null )
            return false;

        $domain = explode('.', $language->poedit_mo);
        $domain = $domain[0];

        if ( ! ($locale = $this->getLocale($locale)) )
            return false;

        putenv('LC_ALL='.$locale);
        putenv('LC_MESSAGES='.$locale);
        putenv('LC_COLLATE='.$locale);
        putenv('LANGUAGE='.$locale);
        setlocale(LC_ALL, $locale.'.UTF-8');
        setlocale(LC_MESSAGES, $locale.'.UTF-8');
        setlocale(LC_COLLATE, $locale.'.UTF-8');
        bindtextdomain($domain, $this->getGettextPath());
        textdomain($domain);
    }

    public function createLocale($locale)
    {
        if ( ! ($locale = $this->getLocale($locale)) )
            return false;

        $t = new Translations();

        $this->setTranslationsHeaders($t, $locale);

        $locale_path = $this->getLocalePath( $locale );

        $po_path = $this->getLocalePath( $locale, $locale . '.po' );

        //Create new directory if not exists
        if ( ! $this->filesystem->isDirectory($locale_path) || ! file_exists($po_path) )
        {
            if ( ! $this->filesystem->isDirectory($locale_path) )
                $this->filesystem->makeDirectory( $locale_path, 0775, true);

            //Create gitignore
            if ( ! file_exists( $this->getGettextPath('.gitignore') ) )
                $this->filesystem->put( $this->getGettextPath('.gitignore'), '*.mo' );

            //Create new template file
            $t->toPoFile($po_path);

            return true;
        }

        return false;

    }

    public function renameLocale($old, $new)
    {
        if ( ! ($old = $this->getLocale($old)) )
            return false;

        if ( ! ($new = $this->getLocale($new)) )
            return false;

        $old_path = $this->getGettextPath( $old );

        if ( ! $this->filesystem->isDirectory($old_path) )
            return $this->createLocale($new);

        if ( $old == $new )
            return false;

        $this->filesystem->move( $old_path, $this->getGettextPath( $new ), true);

        return true;
    }

    /*
     * Copy translation file from uploads to laravel storage
     */
    public function generateMoFiles($locale, $row)
    {
        if ( ! ($locale = $this->getLocale($locale)) )
            return false;

        if ( ! $row->poedit_po || ! $row->poedit_mo )
            return true;

        //Path to uploaded file by administrator
        $uploaded_path_po = $row->poedit_po->basepath;

        //Path to moved file from uploads to storage
        $locale_mo_path = $this->getLocalePath($locale, $row->poedit_mo);

        $locale_po_path = $this->getLocalePath($locale, $locale . '.po');

        //If pofile has been changed, then generate new mo file and remove previous one
        if ( !file_exists($locale_po_path) || md5_file($uploaded_path_po) != md5_file($locale_po_path))
        {
            $this->filesystem->copy($uploaded_path_po, $locale_po_path);

            $translations = Translations::fromPoFile($uploaded_path_po);

            $translations->toMoFile($locale_mo_path);

            $this->removeOldMoFiles($locale, $row->poedit_mo);
        }
    }

    /*
     * Change filename po mo files,
     * because .mo files need to be unique
     */
    public function getMoFilename()
    {
        return date('d-m-Y-h-i-s') . '.mo';
    }

    public function getTranslations($language)
    {
        $locale = $this->getLocale($language->slug);

        $po_path = $this->getLocalePath( $locale, $locale . '.po' );

        //Check if actual language has been modified sync last source changes
        $can_sync = $this->compareCacheKey('lang_modification.'.$locale);

        //If file does not exists, then sync and generate translations
        if ( ! file_exists($po_path) || $can_sync ){
            $this->syncTranslates($language);
        }

        $translations = Translations::fromPoFile($po_path);

        //Get all plural forms
        $string = JSON::toString($translations);
        $array = json_decode($string);
        $array->plurals = [];

        foreach ($translations as $translation)
        {
            if ( $translation->hasPlural() )
                $array->plurals[] = $translation->getOriginal();
        }

        return $array;
    }

    /*
     * Return js plugin for translates
     */
    public function getJSPlugin()
    {
        $localization = Localization::get();

        $timestamp = 0;

        if ( $localization->poedit_po && file_exists($localization->poedit_po->path) )
            $timestamp = filemtime($localization->poedit_po->path);

        return action('\Gogol\Admin\Controllers\GettextController@index')
                    . '?lang='.($localization ? $localization->slug : '')
                    . '&t='.$timestamp;
    }

    /*
     * Return all js translations
     */
    public function getJSTranslations($lang)
    {
        $locale = $this->getLocale($lang);

        $po_path = $this->getLocalePath( $locale, $locale . '.po' );

        if ( ! file_exists($po_path) )
            return '[]';

        $timestamp = filemtime($po_path);

        $key = 'js_bundle';

        //If we need restore cached translations data
        if ( $this->compareCacheKey($key.'_'.$lang, $timestamp) ){
            Cache::forget($key);
        }

        return Cache::rememberForever($key, function() use ($po_path) {
            $items = new Translations;

            $translations = Translations::fromPoFile($po_path);

            foreach ($translations as $translation)
            {
                $comment = strtolower(implode('', $translation->getComments()));

                //Push js translates into translations collection
                if ( strpos($comment, '.vue') !== false || strpos($comment, '.js') !== false )
                    $items[] = $translation;
            }

            return JSON::toString($translations);
        });
    }

    /*
     * Update translations for specific language from array of changes
     */
    public function updateTranslations($language, $changes)
    {
        $locale = $this->getLocale($language->slug);

        $po_path = $this->getLocalePath( $locale, $locale . '.po' );

        $translations = Translations::fromPoFile($po_path);

        foreach ($changes as $key => $value)
        {
            //Update existing translation
            if ( $translation = $translations->find(null, $key) )
            {
                //Update plural form
                if ( is_array($value) )
                {
                    $translation->setTranslation($value[0]);
                    $translation->setPluralTranslations(array_slice($value, 1));
                }

                //Update normal form
                else {
                    $translation->setTranslation($value);
                }
            }
        }

        $this->rebuildGettextFiles($language, $translations);

        return true;
    }

    //Set correct translations headers
    private function setTranslationsHeaders($translations, $locale)
    {
        $translations->setLanguage($locale);
        $translations->setHeader('Project-Id-Version', 'Translations powered by CrudAdmin.com');
    }

    /*
     * Rebuild po/mo files from translations source and update uploaded files of specific language
     */
    public function rebuildGettextFiles($language, $translations)
    {
        $locale = $this->getLocale($language->slug);

        $this->setTranslationsHeaders($translations, $locale);

        $mo_filename = $this->getMoFilename();
        $po_filename = $locale . '-' . date('d-m-Y-h-i-s') . '.po';

        $mo_path = $this->getLocalePath($locale, $mo_filename);
        $po_path = $this->getLocalePath($locale, $locale . '.po');

        //Make missing directories
        File::makeDirs($this->getLocalePath($locale));
        File::makeDirs($language->filePath('poedit_po'));

        //Save into mo file
        $translations->toMoFile($mo_path);
        $translations->toPoFile($po_path);

        //Copy generated mo into uploads folder for avaiable download mo file
        copy($po_path, $language->filePath('poedit_po', $po_filename));

        $this->removeOldMoFiles($locale, $mo_filename);

        $language->update([ 'poedit_mo' => $mo_filename, 'poedit_po' => $po_filename ]);
    }

    /*
     * Remove all old mo files from gettext storage directory
     */
    public function removeOldMoFiles($locale, $except = null)
    {
        $files = scandir($this->getLocalePath( $locale ));

        foreach ($files as $file) {
            if ( last(explode('.', $file)) == 'mo' && $file != $except )
                unlink($this->getLocalePath( $locale, $file ));
        }
    }

    /*
     * Return modified options for adding _ parser
     */
    private function getDecoderOptions()
    {
        return [
            'functions' => PhpCode::$options['functions'] + [ '_' => 'gettext' ],
            'facade' => \Illuminate\Support\Facades\Blade::class,
        ];
    }

    /*
     * Return collector type by file
     */
    private function getCollectorType($file)
    {
        $extension = $file->getExtension();

        if ($extension == 'php'){
            //Collect from blade file
            if ( substr($file->getFilename(), -10) === '.blade.php' )
                return 'Blade';

            return 'PhpCode';
        }

        else if ($extension == 'js')
            return 'JsCode';

        else if ($extension == 'vue')
            return 'VueJs';

        return null;
    }

    /*
     * Add sources from directories into translation class
     */
    private function mergeDirectoryTranslations($path, $translations)
    {
        //Use relative path or laravel base path
        $path = base_or_relative_path($path);

        if ( ! file_exists($path) )
            return;

        //Foreach all files and merge translations by file type
        foreach ($this->filesystem->allFiles($path) as $file)
        {
            $type = $this->getCollectorType($file);

            if ( $type && $sources = Translations::{'from'.$type.'File'}((string)$file, $this->getDecoderOptions()) ){
                if ( in_array($type, ['JsCode', 'VueJs']) )
                    $sources = $this->setJSComment($sources, $file);

                $translations->mergeWith($sources);
            }
        }
    }

    /*
     * Add into translate, that given text is from js file
     */
    private function setJSComment($translates, $file)
    {
        foreach ($translates as $translate) {
            $translate->addComment($file->getFileName());
        }

        return $translates;
    }

    /*
     * Return modification timestamp of last modified file
     */
    private function getSourceModificationTimestamp()
    {
        if ( $this->modification_timestamp )
            return $this->modification_timestamp;

        $views_paths = $this->getSourcePaths(false);

        //Get list of modified files
        $modified = [];

        foreach ($views_paths as $path) {
            $path = base_or_relative_path($path);

            if ( ! file_exists($path) )
                continue;

            foreach ($this->filesystem->allFiles( $path ) as $file)
            {
                $modified[filemtime($file)] = (string)$file;
            }
        }

        ksort($modified);

        return $this->modification_timestamp = last(array_keys($modified));
    }

    /*
     * Check cached key has same value as timestamp of modification source
     */
    private function compareCacheKey($key, $timestamp = null)
    {
        $timestamp = $timestamp ?: $this->getSourceModificationTimestamp();

        //If no modify time is in cache
        $has_in_cache = Cache::has($key);

        //Get modification date from cache
        $cache_timestamp = Cache::rememberForever($key, function() use($timestamp){
            return $timestamp;
        });

        //If modification date was not in cache, or modification dates are not matching, then reload gettext resources
        if ( $timestamp != $cache_timestamp || ! $has_in_cache ){
            Cache::forget($key);
            Cache::forever($key, $timestamp);

            return true;
        }

        return false;
    }

    /*
     * Collect all translations in application and merge them with existing/new translation files
     */
    public function syncTranslates($language)
    {
        $locale = $this->getLocale($language->slug);

        $po_path = $this->getLocalePath( $locale, $locale . '.po' );

        if ( ! file_exists($po_path) )
            $this->createLocale($language->slug);

        $translations = Translations::fromPoFile($po_path);

        $views_paths = $this->getSourcePaths();

        //Foreach all gettext source directories
        foreach ($views_paths as $path)
            $this->mergeDirectoryTranslations($path, $translations);

        $this->rebuildGettextFiles($language, $translations);

        return JSON::toString($translations);
    }
}
?>