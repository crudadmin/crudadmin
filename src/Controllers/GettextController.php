<?php

namespace Admin\Controllers;

use Admin;
use AdminLocalization;
use Admin\Helpers\Localization\LocalizationHelper;
use Facades\Admin\Helpers\Localization\JSTranslations;
use Gettext;
use Symfony\Component\HttpFoundation\Response;
use EditorMode;
use Ajax;

class GettextController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  string  $localizationClass
     */
    public function index($localizationClass = 'Localization', $lang = null)
    {
        $lang = request('lang', $lang);

        $translations = JSTranslations::getJSTranslations($lang, $localizationClass::getModel());

        if ( EditorMode::isActiveTranslatable() ) {
            $rawTranslations = JSTranslations::getRawJSTranslations($lang, $localizationClass::getModel());
        } else {
            $rawTranslations = '[]';
        }

        $js = view('admin.crud::translates', compact('translations', 'rawTranslations'))->render();

        $response = new Response($js, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'max-age=2592000,public',
        ]);

        //We does not want cookies send by this request
        //Because some CDN may not cache request with cookies
        $response->send();
        die;
    }

    /**
     * Returns admin translates
     */
    public function adminIndex()
    {
        return $this->index(AdminLocalization::class);
    }

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
        if ( !admin() || !admin()->hasAccess(get_class($model), $permission ?: 'read') ) {
            Ajax::permissionsError();
        }

        if ( is_numeric($idOrSlug) ) {
            return $model->findOrFail($idOrSlug);
        }

        return $model->where('slug', $idOrSlug)->firstOrFail();
    }

    /*
     * Return all translations for specifics language
     */
    public function getTranslations($id, $table)
    {
        $language = $this->getTranslationRow($id, $table, 'read');

        $translations = JSTranslations::getTranslations($language);

        return response()->json($translations);
    }

    /*
     * Update translations for specific language
     */
    public function updateTranslations($id, $table = null)
    {
        $language = $this->getTranslationRow($id, $table, 'update');

        $changes = request('changes', []);

        JSTranslations::updateTranslations($language, $changes);
    }

    /*
     * Download updated poedit file
     */
    public function downloadTranslations($id, $table)
    {
        $language = $this->getTranslationRow($id, $table, 'read');

        JSTranslations::checkIfIsUpToDate($language);

        return response()->download($language->poedit_po->basepath);
    }

    /**
     * Update state
     *
     * @return
     */
    public function updateEditorState($lang)
    {
        $state = request('state');

        EditorMode::setState($state);

        if ( request('response') && EditorMode::isActiveTranslatable() ) {
            return $this->index('Localization', $lang);
        }

        return EditorMode::isActive() ? 1 : 0;
    }
}
