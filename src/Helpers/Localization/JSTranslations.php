<?php

namespace Admin\Helpers\Localization;

use Admin\Core\Helpers\Storage\AdminFile;
use Cache;
use Carbon\Carbon;
use File;
use Gettext;
use Gettext\Extractors\PhpCode;
use Gettext\Generators\Json;
use Gettext\Translations;
use Illuminate\Filesystem\Filesystem;
use \Illuminate\Support\Facades\Blade;
use \SplFileInfo;
use EditorMode;

class JSTranslations
{
    const CACHE_RESOURCES_KEY = 'lang_modification';
    /*
     * Flags for translations
     */
    const GETTEXT_FLAGS = [
        'javascript' => 'js-flag', //translation is from javascript/vuejs template
        'missing' => 'missing-in-source', //missing translation from source
        'isRaw' => 'raw-text', //missing translation from source
    ];

    protected $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem;
    }

    public function getGettextFlags()
    {
        return self::GETTEXT_FLAGS;
    }

    public function getJavascript($localizationClass = 'Localization', $lang = null)
    {
        $model = $localizationClass::getModel();

        $translations = $this->getJsonTranslations($lang, $model);

        //Return original translations for editor purposes
        $rawTranslations = EditorMode::isActiveTranslatable()
            ? json_encode($this->getRawTranslations($lang, $model))
            : '[]';

        return view('admin::translates', compact('translations', 'rawTranslations'))->render();
    }

    /**
     * Return all js translations
     *
     * @param  string  $lang
     * @param  string  $localizationClass
     * @return  string
     */
    public function getJsonTranslations($lang, $model)
    {
        return $this->getCachableTranslates($lang, $model, 'jsBundle', function ($poPath) {
            $translations = Translations::fromPoFile(
                Gettext::getStorage()->path($poPath)
            );

            return JSON::toString($translations);
        });
    }

    /**
     * Return all raw js translations
     *
     * @param  string  $lang
     * @param  string  $localizationClass
     * @return  string
     */
    private function getRawTranslations($lang, $model)
    {
        return $this->getCachableTranslates($lang, $model, 'jsBundleRaw', function ($poPath) {
            $rawTranslations = [];

            $translations = Translations::fromPoFile(
                Gettext::getStorage()->path($poPath)
            );

            foreach ($translations as $translation) {
                if ( in_array(self::GETTEXT_FLAGS['isRaw'], $translation->getFlags()) ) {
                    $rawTranslations[] = $translation->getOriginal();
                }
            }

            return $rawTranslations;
        });
    }

    /**
     * Get translates by actual version of resources
     *
     * @param  string  $lang
     * @param  AdminModel  $model
     * @param  string  $cacheKey
     * @param  closure  $callback
     * @return  string
     */
    public function getCachableTranslates($lang, $model, $cacheKey, $callback)
    {
        $language = $model->where('slug', $lang)->firstOrFail();

        $poPath = $language->localPoPath;

        if (Gettext::getStorage()->exists($poPath) == false) {
            return '[]';
        }

        $timestamp = Gettext::getStorage()->lastModified($poPath);

        //Set cache key for specific language
        $cacheKey .= '.'.$lang;

        //If we need restore cached translations data
        if ($this->hasCachedFilesBeenChanged($language, $cacheKey, $timestamp)) {
            Cache::forget($cacheKey);
        }

        return Cache::rememberForever($cacheKey, function() use ($callback, $poPath) {
            return $callback($poPath);
        });
    }

    /**
     * Add or remove missing flags for translations. Also disable/enable this translations.
     *
     * @param  Gettext\Translation  $translations
     * @param  Gettext\Translation  $loadedTranslations
     * @return void
     */
    public function markMissingTranslations($translations, $loadedTranslations)
    {
        //All missing translations from existing po files mark as missing in comments
        foreach ($translations as $key => $translation) {
            //If translation does not exists in new loaded string,
            if ( ! array_key_exists($key, (array)$loadedTranslations) ) {
                //Remove missing translations
                if ( config('admin.gettext_remove_missing') === true ) {
                    unset($translations[$key]);
                }

                //flag missing translations
                else {
                    //if is not marked as missing already
                    if ( ! in_array(self::GETTEXT_FLAGS['missing'], $translation->getFlags()) ) {
                        $translation->addFlag(self::GETTEXT_FLAGS['missing']);
                    }
                }
            }

            //If text does exists now, but has been marked as inexisting before..
            elseif ( in_array(self::GETTEXT_FLAGS['missing'], $translation->getFlags()) ) {
                $flags = array_diff($translation->getFlags(), ['missing-in-source']);
                $translation->deleteFlags();

                foreach ($flags as $flag) {
                    $translation->addFlag($flag);
                }
            }
        }
    }

    /**
     * Check if given locale is up to date
     *
     * @param  Admin\Eloquent\AdminModel  $language
     */
    public function checkIfIsUpToDate($language)
    {
        $locale = $language->locale;

        $poPath = $language->localPoPath;

        $cacheKey = self::CACHE_RESOURCES_KEY.'.'.$locale;

        //Check if actual language has been modified sync last source changes
        $cacheKeyChanged = $this->hasCachedFilesBeenChanged($language, $cacheKey);

        $poFilesMissing = (
            //If poPath does not exists
            Gettext::getStorage()->exists($poPath) == false
            //if language poPath does not exists
            || !$language->poedit_po || !$language->poedit_po->exists()
        );

        if ($poFilesMissing || $cacheKeyChanged ) {
            $this->syncTranslates($language, $cacheKey);
        }
    }

    /*
     * Update translations for specific language from array of changes
     */
    public function updateTranslations($language, $changes)
    {
        $translations = Translations::fromPoFile($language->localPoBasepath);

        foreach ($changes as $key => $value) {
            //Update existing translation
            if ($translation = $translations->find(null, $key)) {
                //Update plural form
                if (is_array($value)) {
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

    /*
     * Return modification timestamp of last modified file
     */
    private function getSourceModificationTimestamp($language)
    {
        $viewPaths = $language->getSourcePaths();

        //Get list of modified files
        $modified = [];

        foreach ($viewPaths as $path) {
            $path = base_or_relative_path($path);

            if (! file_exists($path)) {
                continue;
            }

            foreach ($this->getAllPathFiles($path) as $file) {
                $modified[filemtime($file)] = (string) $file;
            }
        }

        ksort($modified);

        return last(array_keys($modified));
    }


    /*
     * Check cached key has same value as timestamp of modification source
     */
    private function hasCachedFilesBeenChanged($language, $key, $timestamp = null)
    {
        $cacheKey = implode(';', $language->getSourcePaths());

        $gettextPathsHash = md5($cacheKey);

        $key .= $gettextPathsHash;

        $timestamp = $timestamp ?: $this->getSourceModificationTimestamp($language);

        //If no modify time is in cache
        $hasInCache = Cache::has($key);

        //Get modification date from cache
        $cachedTimestamp = Cache::rememberForever($key, function () use ($timestamp) {
            return $timestamp;
        });

        //If modification date was not in cache, or modification dates are not matching, then reload gettext resources
        if ( $timestamp !== $cachedTimestamp || $hasInCache === false ) {
            Cache::forever($key, $timestamp);

            return true;
        }

        return false;
    }


    /*
     * Collect all translations in application and merge them with existing/new translation files
     */
    public function syncTranslates($language, $cacheKey)
    {
        $locale = Gettext::getLocale($language->slug);

        $poPath = $language->localPoPath;

        //Run trigger before files sync build
        if ( method_exists($language, 'beforeGettextFilesSync') ){
            $language->beforeGettextFilesSync();

            //We need refresh cache times keys if something has been changed in this step...
            $this->hasCachedFilesBeenChanged($language, $cacheKey);
        }

        $loadedTranslations = new Translations;

        //If cached resource exists
        if ( Gettext::getStorage()->exists($poPath) ) {
            $translations = Translations::fromPoFile(
                $language->localPoBasepath
            );
        }

        //If cloud language resource exists
        else if ( $language->poedit_po && $language->poedit_po->exists() ) {
            $translations = Translations::fromPoString($language->poedit_po->get());
        }

        //Empty translation string
        else {
            $translations = new Translations;
        }

        $viewPaths = $language->getSourcePaths();

        //Foreach all $this->ource directories
        foreach ($viewPaths as $path) {
            $this->mergeDirectoryTranslations(
                $path,
                $loadedTranslations,
                $language->loadGettextFilesWithReferences()
            );
        }

        //Mark and disable missing translations
        $this->markMissingTranslations($translations, $loadedTranslations);

        //Load all loaded translations with old translations
        $translations->mergeWith($loadedTranslations);

        $this->rebuildGettextFiles($language, $translations);

        return JSON::toString($translations);
    }

    /*
     * Add sources from directories into translation class
     */
    private function mergeDirectoryTranslations($path, $translations, $withReferences = true)
    {
        //Use relative path or laravel base path
        $path = base_or_relative_path($path);

        if (! file_exists($path)) {
            return;
        }

        //Foreach all files and merge translations by file type
        foreach ($this->getAllPathFiles($path) as $file) {
            $type = $this->getCollectorType($file);

            if ($type && $sources = Translations::{'from'.$type.'File'}((string) $file, $this->getDecoderOptions())) {
                if (in_array($type, ['JsCode', 'VueJs'])) {
                    $sources = $this->setJSFlag($sources, $file);
                }

                if (in_array($type, ['VueJs'])) {
                    $sources = $this->markRawStringsFromVuejs($sources, $file);
                }

                //Get raw texts
                if (in_array($type, ['Blade'])) {
                    $sources = $this->markRawStrings($sources, $file);
                }

                //We does not want references in AdminLanguages.
                //Refernces are location of translates in given files
                if ( $withReferences === false ) {
                    foreach ((array)$sources as $source) {
                        $source->deleteReferences();
                    }
                }

                $translations->mergeWith($sources);
            }
        }
    }

    private function getAllPathFiles($path)
    {
        if ( is_dir($path) ) {
            return $this->filesystem->allFiles($path);
        } else {
            return [ new SplFileInfo($path) ];
        }
    }

    public function markRawStrings($sources, $file)
    {
        $content = $file->getContents();

        $rawTexts = [];

        //Get all raw outputs
        preg_match_all('/\{\!\!(.*?)\!\!\}/', $content, $matches);

        foreach (@$matches[1] ?: [] as $sentence) {
            preg_match_all("/(?:(?:\"(?:\\\\\"|[^\"])+\")|(?:'(?:\\\'|[^'])+'))/is", $sentence, $quotas);

            $rawTexts = array_merge($rawTexts, array_map(function($item){
                return substr($item, 1, -1);
            }, $quotas[0]));
        }

        //Check all sources, if are in given raw elements
        foreach ($sources as $source) {
            if ( in_array($source->getOriginal(), $rawTexts) ) {
                $source->addFlag(self::GETTEXT_FLAGS['isRaw']);
            }
        }

        return $sources;
    }

    public function markRawStringsFromVuejs($sources, $file)
    {
        $content = $file->getContents();

        $rawTexts = [];

        //Get all raw outputs
        preg_match_all('/v-html\=\"(.*?)\"/', $content, $matches);

        foreach ($matches[1] ?? [] as $sentence) {
            preg_match_all("/(?:(?:\"(?:\\\\\"|[^\"])+\")|(?:'(?:\\\'|[^'])+'))/is", $sentence, $quotas);

            $rawTexts = array_merge($rawTexts, array_map(function($item){
                return substr($item, 1, -1);
            }, $quotas[0]));
        }

        //Check all sources, if are in given raw elements
        foreach ($sources as $source) {
            if ( in_array($source->getOriginal(), $rawTexts) ) {
                $source->addFlag(self::GETTEXT_FLAGS['isRaw']);
            }
        }

        return $sources;
    }

    /*
     * Return modified options for adding _ parser
     */
    private function getDecoderOptions()
    {
        return [
            'functions' => PhpCode::$options['functions'] + ['_' => 'gettext'],
            'facade' => Blade::class,
        ];
    }

    /*
     * Return collector type by file
     */
    private function getCollectorType($file)
    {
        $extension = $file->getExtension();

        if ($extension == 'php') {
            //Collect from blade file
            if (substr($file->getFilename(), -10) === '.blade.php') {
                return 'Blade';
            }

            return 'PhpCode';
        } elseif ($extension == 'js') {
            return 'JsCode';
        } elseif ($extension == 'vue') {
            return 'VueJs';
        }
    }

    /*
     * Add into translate, that given text is from js file
     */
    private function setJSFlag($translates, $file)
    {
        foreach ($translates as $translate) {
            $translate->addFlag(self::GETTEXT_FLAGS['javascript']);
        }

        return $translates;
    }

    /*
     * Rebuild po/mo files from translations source and update uploaded files of specific language
     */
    public function rebuildGettextFiles($language, $translations)
    {
        $locale = $language->locale;

        Gettext::setTranslationsHeaders($translations, $locale);

        //Create uploads po file
        $poFilename = $language->localePrefixWithSlash.$locale.'-'.time().'.po';

        //Get storage po file path
        $localePoPath = $language->localPoPath;
        $localePoBasepath = $language->localPoBasepath;

        //Make missing directories
        AdminFile::makeDirs(dirname($localePoBasepath));

        //Save into po file
        $translations->toPoFile($localePoBasepath);

        //TODO: unrefactored code
        // //If fresh generated storage po_file is same with existing po file in uploads folder
        // if ( $language->getPoPath() && @md5_file($localePoBasepath) === @md5_file($language->getPoPath()->basepath) ) {
        //     return;
        // }

        if ( $language->exists ) {
            $fieldStorage = $language->getFieldStorage('poedit_po');
            $fieldStoragePath = $language->getStorageFilePath('poedit_po', $poFilename);

            $fieldStorage->writeStream(
                $fieldStoragePath,
                Gettext::getStorage()->readStream($localePoPath)
            );

            //Remove previously generated file
            if ( $language->poedit_po && $language->poedit_po->exists ) {
                $language->poedit_po->delete();
            }

            $language->update(['poedit_po' => $poFilename]);
        }

        //Regenerate new mo file
        Gettext::generateMoFile($language);
    }
}
