<?php

namespace Admin\Helpers\Localization;

use Admin;
use Facades\Admin\Helpers\Localization\JSTranslations;
use Gettext;
use Gettext\Generators\Json;
use Gettext\Translations;

class GettextEditor
{
    /**
     * Returns translation row by given table
     *
     * @param  string/number  $idOrSlug
     * @param  string  $table
     * @return  AdminModel|null
     */
    public function getTranslationRow($idOrSlug, $table, $permission = null)
    {
        $model = Admin::getModelByTable($table ?: 'languages');

        //If user does not have permissions
        if ( !admin() || !admin()->hasAccess($model, $permission ?: 'read') ) {
            autoAjax()->permissionsError()->throw();
        }

        if ( is_numeric($idOrSlug) ) {
            return $model->findOrFail($idOrSlug);
        }

        return $model->where('slug', $idOrSlug)->firstOrFail();
    }


    /**
     * Return translations array
     *
     * @param  Admin\Eloquent\AdminModel  $language
     * @return  array
     */
    public function getTranslations($language)
    {
        Gettext::setGettextPropertiesModel($language);

        JSTranslations::checkIfIsUpToDate($language);

        return Translations::fromPoFile($language->localPoBasepath);
    }

    public function getEditorResponse($language)
    {
        $translations = $this->getTranslations($language);

        return [
            'translations' => json_decode(JSON::toString($translations), true),
            'plurals' => $this->getPlurals($translations),
            'missing' => $this->getMissingTranslations($translations),
            'raw' => $this->getRawTranslations($translations),
            'source' => $this->getSourceTranslations($language)
        ];
    }

    /**
     * Add plurals into translations array
     *
     * @param  Gettext\Translations  $translations
     */
    private function getPlurals(Translations $translations)
    {
        $plurals = [];

        foreach ($translations as $translation) {
            if ($translation->hasPlural()) {
                $plurals[] = $translation->getOriginal();
            }
        }

        return $plurals;
    }

    /**
     * Add missing translations into translations array
     *
     * @param  Gettext\Translations  $translations
     */
    private function getMissingTranslations(Translations $translations)
    {
        $missing = [];

        foreach ($translations as $translation) {
            if (in_array(JSTranslations::getGettextFlags()['missing'], $translation->getFlags())) {
                $missing[] = $translation->getOriginal();
            }
        }

        return $missing;
    }

    /**
     * Add raw translations into translations array
     *
     * @param  object  $array
     * @param  Gettext\Translations  $translations
     */
    private function getRawTranslations(Translations $translations)
    {
        $raw = [];

        foreach ($translations as $translation) {
            if (in_array(JSTranslations::getGettextFlags()['isRaw'], $translation->getFlags())) {
                $raw[] = $translation->getOriginal();
            }
        }

        return $raw;
    }

    public function getSourceTranslations($language)
    {
        //Disable source response for source language
        if ( $language->is_source ){
            return [];
        }

        $sourceLanguage = $language->newQuery()->where('is_source', true)->first();

        return json_decode(JSON::toString(
            $this->getTranslations($sourceLanguage)
        ), true);
    }
}
