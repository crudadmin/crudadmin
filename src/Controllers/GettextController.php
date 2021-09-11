<?php

namespace Admin\Controllers;

use Admin;
use AdminLocalization;
use Admin\Helpers\Localization\LocalizationHelper;
use Facades\Admin\Helpers\Localization\JSTranslations;
use Gettext;
use Symfony\Component\HttpFoundation\Response;
use EditorMode;
use Localization;
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

        $js = $this->getJavascript($localizationClass, $lang);

        $response = new Response($js, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'max-age=2592000,public',
        ]);

        //We does not want cookies send by this request
        //Because some CDN may not cache request with cookies
        $response->send();
        die;
    }

    public function getJavascript($localizationClass = 'Localization', $lang = null)
    {
        $translations = JSTranslations::getJSTranslations($lang, $localizationClass::getModel());

        //Return original translations for editor purposes
        if ( EditorMode::isActiveTranslatable() ) {
            $rawTranslations = JSTranslations::getRawJSTranslations($lang, $localizationClass::getModel());
        } else {
            $rawTranslations = '[]';
        }

        return view('admin::translates', compact('translations', 'rawTranslations'))->render();
    }

    public function getJson($lang = null)
    {
        if ( config('admin.gettext_json', false) === false ){
            abort(404);
        }

        $lang = $lang ?: Localization::get()->slug;

        $translations = JSTranslations::getJSTranslations($lang, Localization::getModel());

        return $translations;
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
        if ( !admin() || !admin()->hasAccess($model, $permission ?: 'read') ) {
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

        return $language->poedit_po->getStorage()->download(
            $language->poedit_po->path
        );
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

    public function switchAdminLanguage($languageId)
    {
        admin()->update([
            'language_id' => $languageId
        ]);

        return 1;
    }
}
