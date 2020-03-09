<?php

namespace Admin\Helpers;

use Admin;
use AdminLocalization;
use Admin\Helpers\File;
use Facades\Admin\Helpers\Localization\JSTranslations;
use Admin\Helpers\Localization\LocalizationHelper;
use App\Core\Models\Language;
use Gettext\Translations;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use EditorMode;
use Localization;

class Gettext
{
    protected $basepath = 'storage/app/lang';

    protected $gettextDir = 'gettext';

    protected $sourcePaths = [];

    protected $supported_codes = ['fr_FR', 'sr_RS', 'es_ES', 'kl_GL', 'ts_ZA', 'ar_IQ', 'ti_ET', 'tt_RU', 'de_DE', 'es_CL', 'sw_TZ', 'lv_LV', 'cs_CZ', 'cz' => 'cs_CZ', 'ur_IN', 'id_ID', 'ar_QA', 'ks_IN', 'ar_YE', 'lg_UG', 'fo_FO', 'ka_GE', 'aa_DJ', 'es_UY', 'en_US', 'sq_MK', 'os_RU', 'so_DJ', 'ja_JP', 'ar_KW', 'ca_ES', 'gl_ES', 'eo_US', 'nb_NO', 'af_ZA', 'nl_BE', 'pa_IN', 'es_US', 'sd_IN', 'li_BE', 'pt_PT', 'nl_NL', 'is_IS', 'br_FR', 'sq_AL', 'so_ET', 'es_VE', 'gu_IN', 'ar_DZ', 'sc_IT', 'tt_RU', 'ca_FR', 'aa_ER', 'ar_SY', 'ff_SN', 'kk_KZ', 'dz_BT', 'bo_CN', 'mg_MG', 'zh_CN', 'ar_LY', 'th_TH', 'sv_FI', 'fi_FI', 'ar_IN', 'ta_IN', 'br_FR', 'kn_IN', 'eu_FR', 'az_AZ', 'zu_ZA', 'ar_EG', 'st_ZA', 'so_KE', 'hy_AM', 'bn_BD', 'rw_RW', 'my_MM', 'es_CU', 'ar_OM', 'he_IL', 'pl_PL', 'mt_MT', 'ht_HT', 'bg_BG', 'mn_MN', 'ps_AF', 'ru_UA', 'cv_RU', 'ms_MY', 'ar_BH', 'oc_FR', 'te_IN', 'wa_BE', 'nr_ZA', 'ar_JO', 'iu_CA', 'eu_ES', 'ko_KR', 'et_EE', 'tg_TJ', 'uk_UA', 've_ZA', 'yo_NG', 'wo_SN', 'mi_NZ', 'ga_IE', 'ku_TR', 'fy_DE', 'tl_PH', 'ne_NP', 'fy_NL', 'ga_IE', 'se_NO', 'bs_BA', 'aa_ET', 'es_SV', 'ca_ES', 'tn_ZA', 'xh_ZA', 'ca_IT', 'es_CO', 'lb_LU', 'tr_TR', 'sa_IN', 'pa_PK', 'POSIX', 'nn_NO', 'el_CY', 'uz_UZ', 'es_BO', 'es_EC', 'bo_IN', 'sv_SE', 'es_CR', 'as_IN', 'lo_LA', 'zh_SG', 'sl_SI', 'ug_CN', 'gv_GB', 'hr_HR', 'yi_US', 'cy_GB', 'dv_MV', 'or_IN', 'es_PA', 'pt_PT', 'bn_IN', 'ru_RU', 'be_BY', 'es_HN', 'zh_TW', 'an_ES', 'eu_FR', 'ik_CA', 'ti_ER', 'ar_TN', 'ur_PK', 'om_KE', 'fi_FI', 'ar_AE', 'it_IT', 'sd_PK', 'es_AR', 'gl_ES', 'ml_IN', 'sd_IN', 'fa_IR', 'es_DO', 'es_GT', 'km_KH', 'ig_NG', 'wa_BE', 'es_NI', 'pt_BR', 'ro_RO', 'el_GR', 'be_BY', 'gd_GB', 'ha_NG', 'vi_VN', 'nl_AW', 'ky_KG', 'ks_IN', 'tr_CY', 'eu_ES', 'li_NL', 'nl_NL', 'mr_IN', 'uz_UZ', 'om_ET', 'es_PR', 'kw_GB', 'zh_HK', 'ug_CN', 'nl_BE', 'ar_SA', 'sk_SK', 'hu_HU', 'sv_FI', 'ta_LK', 'hi_IN', 'it_CH', 'es_PY', 'ar_MA', 'lt_LT', 'so_SO', 'am_ET', 'da_DK', 'ca_ES', 'mk_MK', 'sw_KE', 'iw_IL', 'es_MX', 'si_LK', 'ca_AD', 'ss_ZA', 'tk_TM', 'ar_SD', 'it_IT', 'es_PE', 'ar_LB', 'el_GR', 'aa_ER'];

    protected $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem;
    }

    public function setGettextPropertiesModel($model)
    {
        $this->gettextDir = $model->gettextDirectory;
        $this->sourcePaths = $model->sourcePaths();

        return $this;
    }

    public function getBasePath($path = null)
    {
        return base_path($this->basepath.'/'.$path);
    }

    public function getGettextPath($path = null)
    {
        return $this->getBasePath($this->gettextDir.'/'.$path);
    }

    public function getLocalePath($locale, $file = null)
    {
        return $this->getGettextPath($locale.'/LC_MESSAGES/'.$file);
    }

    public function getSourcePaths()
    {
        return $this->sourcePaths;
    }

    public function getSupportedCodes()
    {
        $codes = config('admin.gettext_supported_codes');
        $codes = isset($codes) ? $codes : [];

        return array_merge($codes, $this->supported_codes);
    }

    public function getLocale($locale)
    {
        if (strlen($locale) == 5) {
            return $locale;
        }

        $locales = $this->getSupportedCodes();

        if (array_key_exists($locale, $locales)) {
            return $locales[$locale];
        }

        foreach ($locales as $code) {
            $part = explode('_', $code);

            if ($part[0] == $locale) {
                return $code;
            }
        }

        return false;
    }

    /**
     * Set gettext locale
     *
     * @param  string  $locale
     * @param  string  $poPath
     */
    public function setLocale($locale = null, $poPath)
    {
        //Regenerate mo files if pofile has been changed
        if (
            ! ($locale = $this->getLocale($locale))
            || ($poTimestamp = $this->generateMoFile($locale, $poPath)) === false
        ) {
            return false;
        }

        putenv('LC_ALL='.$locale);
        putenv('LC_MESSAGES='.$locale);
        putenv('LC_COLLATE='.$locale);
        putenv('LANGUAGE='.$locale);
        setlocale(LC_ALL, $locale.'.UTF-8');
        //In windows may not be definted this variable
        if ( defined('LC_MESSAGES') ) {
            setlocale(LC_MESSAGES, $locale.'.UTF-8');
        }
        setlocale(LC_COLLATE, $locale.'.UTF-8');
        bindtextdomain($poTimestamp, $this->getGettextPath());
        textdomain($poTimestamp);
    }

    public function createLocale($locale)
    {
        if (! ($locale = $this->getLocale($locale))) {
            return false;
        }

        $t = new Translations();

        $this->setTranslationsHeaders($t, $locale);

        $locale_path = $this->getLocalePath($locale);

        $poPath = $this->getLocalePath($locale, $locale.'.po');

        //Create new directory if not exists
        if (! $this->filesystem->isDirectory($locale_path) || ! file_exists($poPath)) {
            File::makeDirs($locale_path);

            //Create gitignore
            if (! file_exists($this->getGettextPath('.gitignore'))) {
                $this->filesystem->put($this->getGettextPath('.gitignore'), '*.mo');
            }

            //Create new template file
            $t->toPoFile($poPath);

            return true;
        }

        return false;
    }

    /**
     * Generate mo files from given poFile
     *
     * @param  string  $locale
     * @param  Admin\Helpers\File  $poFile
     */
    public function generateMoFile($locale, $poFile)
    {
        //If locale or pofile is not present
        if (! ($locale = $this->getLocale($locale)) || ! $poFile) {
            return false;
        }

        //Path to uploaded file by administrator
        if ( ! file_exists($poBasePath = $poFile->basepath) ) {
            return false;
        }

        //Get mo filename by timestamp
        $poFileTimestamp = filemtime($poBasePath);
        $moFilename = $poFileTimestamp.'.mo';

        //Path to moved file from uploads to storage
        $storageMoPath = $this->getLocalePath($locale, $moFilename);

        $storagePoPath = $this->getLocalePath($locale, $locale.'.po');

        //If poFile has been changed, then generate new mo file and remove previous one
        if ( !file_exists($storagePoPath) || !file_exists($storageMoPath) ) {
            File::makeDirs(dirname($storagePoPath));

            //Copy given po file to strage folder
            copy($poBasePath, $storagePoPath);

            $translations = Translations::fromPoFile($poBasePath);

            $translations->toMoFile($storageMoPath);

            $this->removeOldMoFiles($locale, $moFilename);
        }

        return $poFileTimestamp;
    }

    /**
     * Return js plugin for translates
     *
     * @param  string  $localizationClass
     * @return  string
     */
    public function getJSPlugin($localizationClass = 'Localization')
    {
        $language = $localizationClass::get();

        //If is allowed frontend web editor, check for for newest translates
        if ( EditorMode::isActiveTranslatable() ) {
            JSTranslations::checkIfIsUpToDate($language);
        }

        $timestamp = 0;

        //We want get timestamp of localization
        if ($language && $language->getPoPath() && file_exists($language->getPoPath()->path)) {
            $timestamp = filemtime($language->getPoPath()->path);
        }

        return asset(action('\Admin\Controllers\GettextController@'.$localizationClass::gettextJsResourcesMethod(), null, false)
                    .'?lang='.($language ? $language->slug : '')
                    .'&t='.$timestamp
                    .'&a='.(EditorMode::isActiveTranslatable() ? Admin::getAssetsVersion() : 0)
        );
    }

    //Set correct translations headers
    public function setTranslationsHeaders($translations, $locale)
    {
        $translations->setLanguage($locale);
        $translations->setHeader('Project-Id-Version', 'Translations powered by CrudAdmin.com for '.request()->getHost().' project.');
    }

    /**
     * Remove all old mo files from gettext storage directory except given
     *
     * @param  string  $locale
     * @param  string  $except
     */
    public function removeOldMoFiles($locale, $except = null)
    {
        $files = scandir($this->getLocalePath($locale));

        foreach ($files as $file) {
            if (last(explode('.', $file)) == 'mo' && $file != $except) {
                unlink($this->getLocalePath($locale, $file));
            }
        }
    }
}
