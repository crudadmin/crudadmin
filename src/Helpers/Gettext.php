<?php
namespace Gogol\Admin\Helpers;

use \App\Core\Models\Language;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Gogol\Admin\Helpers\File;
use Gettext\Translations;
use Gettext\Extractors\PhpCode;
use Storage;
use Cache;

class Gettext
{
    protected $filesystem;

    protected $basepath = 'storage/app/lang';

    protected $cachepath = 'views';

    protected $supported_codes = ['fr_FR', 'sr_RS', 'es_ES', 'kl_GL', 'ts_ZA', 'ar_IQ', 'ti_ET', 'tt_RU', 'de_DE', 'es_CL','sw_TZ', 'lv_LV', 'cs_CZ', 'cz' => 'cs_CZ', 'ur_IN', 'id_ID', 'ar_QA', 'ks_IN', 'ar_YE', 'lg_UG', 'fo_FO', 'ka_GE', 'aa_DJ', 'es_UY', 'en_US', 'sq_MK', 'os_RU', 'so_DJ', 'ja_JP', 'ar_KW', 'ca_ES', 'gl_ES', 'eo_US', 'nb_NO', 'af_ZA', 'nl_BE', 'pa_IN', 'es_US', 'sd_IN', 'li_BE', 'pt_PT', 'nl_NL', 'is_IS', 'br_FR', 'sq_AL', 'so_ET', 'es_VE', 'gu_IN', 'ar_DZ', 'sc_IT', 'tt_RU', 'ca_FR', 'aa_ER', 'ar_SY', 'ff_SN', 'kk_KZ', 'dz_BT', 'bo_CN', 'mg_MG', 'zh_CN', 'ar_LY', 'th_TH', 'sv_FI', 'fi_FI', 'ar_IN', 'ta_IN', 'br_FR', 'kn_IN', 'eu_FR', 'az_AZ', 'zu_ZA', 'ar_EG', 'st_ZA', 'so_KE', 'hy_AM', 'bn_BD', 'rw_RW', 'my_MM', 'es_CU', 'ar_OM', 'he_IL', 'pl_PL', 'mt_MT', 'ht_HT', 'bg_BG', 'mn_MN', 'ps_AF', 'ru_UA', 'cv_RU', 'ms_MY', 'ar_BH', 'oc_FR', 'te_IN', 'wa_BE', 'nr_ZA', 'ar_JO', 'iu_CA', 'eu_ES', 'ko_KR', 'et_EE', 'tg_TJ', 'uk_UA', 've_ZA', 'yo_NG', 'wo_SN', 'mi_NZ', 'ga_IE', 'ku_TR', 'fy_DE', 'tl_PH', 'ne_NP', 'fy_NL', 'ga_IE', 'se_NO', 'bs_BA', 'aa_ET', 'es_SV', 'ca_ES', 'tn_ZA', 'xh_ZA', 'ca_IT', 'es_CO', 'lb_LU', 'tr_TR', 'sa_IN', 'pa_PK', 'POSIX', 'nn_NO', 'el_CY', 'uz_UZ', 'es_BO', 'es_EC', 'bo_IN', 'sv_SE', 'es_CR', 'as_IN', 'lo_LA', 'zh_SG', 'sl_SI', 'ug_CN', 'gv_GB', 'hr_HR', 'yi_US', 'cy_GB', 'dv_MV', 'or_IN', 'es_PA', 'pt_PT', 'bn_IN', 'ru_RU', 'be_BY', 'es_HN', 'zh_TW', 'an_ES', 'eu_FR', 'ik_CA', 'ti_ER', 'ar_TN', 'ur_PK', 'om_KE', 'fi_FI', 'ar_AE', 'it_IT', 'sd_PK', 'es_AR', 'gl_ES', 'ml_IN', 'sd_IN', 'fa_IR', 'es_DO', 'es_GT', 'km_KH', 'ig_NG', 'wa_BE', 'es_NI', 'pt_BR', 'ro_RO', 'el_GR', 'be_BY', 'gd_GB', 'ha_NG', 'vi_VN', 'nl_AW', 'ky_KG', 'ks_IN', 'tr_CY', 'eu_ES', 'li_NL', 'nl_NL', 'mr_IN', 'uz_UZ', 'om_ET', 'es_PR', 'kw_GB', 'zh_HK', 'ug_CN', 'nl_BE', 'ar_SA', 'sk_SK', 'hu_HU', 'sv_FI', 'ta_LK', 'hi_IN', 'it_CH', 'es_PY', 'ar_MA', 'lt_LT', 'so_SO', 'am_ET', 'da_DK', 'ca_ES', 'mk_MK', 'sw_KE', 'iw_IL', 'es_MX', 'si_LK', 'ca_AD', 'ss_ZA', 'tk_TM', 'ar_SD', 'it_IT', 'es_PE', 'ar_LB', 'el_GR', 'aa_ER'];

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

    protected function getLocalePath($locale, $file = null)
    {
        return $this->getGettextPath( $locale . '/LC_MESSAGES/' . $file);
    }

    public function getSourcePaths($add = true)
    {
        $paths = config('admin.gettext_source_paths', []);

        if ( $add == true )
            $paths[] = $this->basepath . '/' .$this->cachepath;

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

    protected function poTemplate($locale)
    {
        $contents = $this->filesystem->get( \Admin::stub('gettext') );

        $contents = str_replace('{locale}', $locale, $contents);
        $contents = str_replace('{timestamp}', date('Y-m-d H:iO'), $contents);

        //Get source paths which contains gettext translates
        $views_paths = $this->getSourcePaths();

        foreach ($views_paths as $key => $path)
        {
            $contents .= "\n".'"X-Poedit-SearchPath-'.$key.': '.$path.'\n"';
        }

        $contents .= "\n";

        return $contents;
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

        $domain = explode('.', $language->poedit_mo->filename);
        $domain = $domain[0];

        if ( ! ($locale = $this->getLocale($locale)) )
            return false;

        putenv('LC_ALL='.$locale);
        putenv('LC_MESSAGES='.$locale);
        putenv('LC_COLLATE='.$locale);
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

        $locale_path = $this->getLocalePath( $locale );

        $po_path = $po_path = $this->getLocalePath( $locale, $locale . '.po' );

        //Create new directory if not exists
        if ( ! $this->filesystem->isDirectory($locale_path) || ! file_exists($po_path) )
        {
            if ( ! $this->filesystem->isDirectory($locale_path) )
                $this->filesystem->makeDirectory( $locale_path, 0775, true);

            //Create gitignore
            if ( ! file_exists( $this->getGettextPath('.gitignore') ) )
                $this->filesystem->put( $this->getGettextPath('.gitignore'), '*.mo' );

            //Create new template file
            $this->filesystem->put( $po_path, $this->poTemplate($locale));

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

    public function renderView($force = false)
    {
        if ( $force !== true && env('APP_DEBUG') == false && env('APP_ENV') != 'production')
        {
            return false;
        }

        $cache_dir = $this->getBasePath( $this->cachepath );

        // Check the output directory
        if (!$this->filesystem->isDirectory($cache_dir)) {
            $this->filesystem->makeDirectory($cache_dir);
        }

        //Clean directory
        $this->filesystem->cleanDirectory( $cache_dir );

        //Copy gitignore
        $this->filesystem->copy( \Admin::stub('gitignore'), $cache_dir.'/.gitignore' );

        //Get source paths which contains gettext translates
        $views_paths = $this->getSourcePaths(false);

        foreach ($views_paths as $path)
        {
            $path = base_path( $path );

            if ( ! file_exists($path) )
                continue;

            $fs = new Filesystem( $path );
            $files = $fs->allFiles( $path );

            $compiler = new BladeCompiler($fs, $cache_dir);

            foreach ($files as $file)
            {
                $filePath = $file->getRealPath();

                $compiler->setPath($filePath);

                $contents = $compiler->compileString($fs->get($filePath));

                $compiledPath = $compiler->getCompiledPath($compiler->getPath());

                $fs->put(
                    rtrim( $compiledPath , '.php' ) . '.php',
                    $contents
                );
            }

        }

        return true;
    }

    /*
     * Copy translation file from uploads to laravel storage
     */
    public function updateLanguage($locale, $paths)
    {
        if ( ! ($locale = $this->getLocale($locale)) )
        {
            return false;
        }

        $this->renderView();

        if ( ! is_array($paths) || ! $paths[0] || ! $paths[1] )
            return true;

        //Path to uploaded file by administrator
        $uploaded_path_po = public_path( $paths[0] );
        $uploaded_path_mo = public_path( $paths[1] );

        //File name
        $filename = basename( $uploaded_path_mo );

        //Path to moved file from uploads to storage
        $locale_path = $this->getLocalePath($locale, $filename);

        //If uploaded file has not been moved
        if ( ! file_exists( $locale_path ) )
        {
            $files_to_remove = $this->filesystem->allFiles( $this->getLocalePath( $locale ) );

            //Removes previous version of translation file
            foreach ($files_to_remove as $file)
            {
                if ( strlen( $file->getFileName() ) == 11 && is_numeric( substr( $file->getFileName(), 0, 8 ) ) )
                {
                    $this->filesystem->delete( $file );
                }
            }

            $this->filesystem->copy( $uploaded_path_mo, $locale_path);
        }

        $locale_po_file = $this->getLocalePath($locale, $locale . '.po');

        //Copy po file
        if ( !file_exists($locale_po_file) || md5_file($uploaded_path_po) != md5_file($locale_po_file))
        {
            $this->filesystem->copy($uploaded_path_po, $locale_po_file);
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

    private function translationsToArray($translations)
    {
        $strings = [];

        foreach ($translations as $translation) {
            $strings[$translation->getOriginal()] = $translation->getTranslation();
        }

        return $strings;
    }

    public function getTranslations($language)
    {
        $locale = $this->getLocale($language->slug);

        $po_path = $this->getLocalePath( $locale, $locale . '.po' );

        //If file does not exists, then sync and generate translations
        if ( ! file_exists($po_path) || $this->needReloadTranslates($locale) ){
            $this->syncTranslates($language);
        }

        $translations = Translations::fromPoFile($po_path);

        return $this->translationsToArray($translations);
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
                $translation->setTranslation($value);
        }

        $this->rebuildGettextFiles($language, $translations);

        return true;
    }

    /*
     * Rebuild po/mo files from translations source and update uploaded files of specific language
     */
    public function rebuildGettextFiles($language, $translations)
    {
        $locale = $this->getLocale($language->slug);

        $mo_filename = $this->getMoFilename();
        $po_filename = $locale . '-' . date('d-m-Y-h-i-s') . '.po';

        $mo_path = $this->getLocalePath($locale, $mo_filename);
        $po_path = $this->getLocalePath($locale, $locale . '.po');

        //Make missing directories
        File::makeDirs($this->getLocalePath($locale));
        File::makeDirs($language->filePath('poedit_mo'));
        File::makeDirs($language->filePath('poedit_po'));

        //Save into mo file
        $translations->toMoFile($mo_path);
        $translations->toPoFile($po_path);

        //Copy generated mo into uploads folder for avaiable download mo file
        copy($mo_path, $language->filePath('poedit_mo', $mo_filename));
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
        ];
    }

    /*
     * Add sources from directories into translation class
     */
    private function mergeDirectoryTranslations($path, $translations)
    {
        $path = base_path( $path );

        if ( ! file_exists($path) )
            return;

        //Foreach all files and merge translations
        foreach ($this->filesystem->allFiles( $path ) as $file)
        {
            $sources = Translations::fromPhpCodeFile((string)$file, $this->getDecoderOptions());

            $translations->mergeWith($sources);
        }
    }

    /*
     * Return modification timestamp of last modified file
     */
    private function getLastModifiedResource()
    {
        $views_paths = $this->getSourcePaths(false);

        //Get list of modified files
        $modified = [];

        foreach ($views_paths as $path) {
            $path = base_path( $path );

            foreach ($this->filesystem->allFiles( $path ) as $file)
            {
                $modified[filemtime($file)] = (string)$file;
            }
        }

        ksort($modified);

        return last(array_keys($modified));
    }

    /*
     * Check if some gettext files has been changed in resources
     */
    public function needReloadTranslates($locale)
    {
        $last_modification = $this->getLastModifiedResource();

        $cache_key = 'last_modification.'.$locale;

        //If no modify time is in cache
        $has_in_cache = Cache::has($cache_key);

        //Get modification date from cache
        $last_modification_cache = Cache::rememberForever($cache_key, function() use($last_modification){
            return $last_modification;
        });

        //If modification date was not in cache, or modification dates are not matching, then reload gettext resources
        if ( $last_modification != $last_modification_cache || ! $has_in_cache ){
            Cache::forget($cache_key);
            Cache::forever($cache_key, $last_modification);

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

        $this->renderView(true);

        $translations = Translations::fromPoFile($po_path);

        $views_paths = $this->getSourcePaths();

        //Foreach all gettext source directories
        foreach ($views_paths as $path)
        {
            $this->mergeDirectoryTranslations($path, $translations);
        }

        $this->rebuildGettextFiles($language, $translations);

        return $this->translationsToArray($translations);
    }
}
?>