<?php

namespace Admin\Controllers;

use Admin;
use AdminLocalization;
use Admin\Helpers\Localization\LocalizationHelper;
use Facades\Admin\Helpers\Localization\JSTranslations;
use Facades\Admin\Helpers\Localization\GettextEditor;
use Gettext;
use Symfony\Component\HttpFoundation\Response;
use EditorMode;
use Localization;

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

        $script = JSTranslations::getJavascript($localizationClass, $lang);

        //We does not want cookies send by this request
        //Because some CDN may not cache request with cookies
        (new Response($script, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'max-age=2592000,public',
        ]))->send();
        die;
    }

    /**
     * Returns admin translates
     */
    public function adminIndex()
    {
        return $this->index(AdminLocalization::class);
    }

    public function getJson($lang = null)
    {
        if ( config('admin.gettext_json', false) === false ){
            abort(404);
        }

        return Localization::getJson($lang);
    }

    /*
     * Return all translations for specifics language
     */
    public function getEditorResponse($id, $table)
    {
        $language = GettextEditor::getTranslationRow($id, $table, 'read');

        return GettextEditor::getEditorResponse($language);
    }

    /*
     * Update translations for specific language
     */
    public function updateTranslations($id, $table = null)
    {
        $language = GettextEditor::getTranslationRow($id, $table, 'update');

        $changes = request('changes', []);

        JSTranslations::updateTranslations($language, $changes);
    }

    /*
     * Download updated poedit file
     */
    public function downloadTranslations($id, $table)
    {
        $language = GettextEditor::getTranslationRow($id, $table, 'read');

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
    public function updateEditorState($languageId)
    {
        $state = request('state');

        EditorMode::setState($state);

        $language = GettextEditor::getTranslationRow($languageId, null, 'read');

        if ( request('response') && EditorMode::isActiveTranslatable() ) {
            return $this->index('Localization', $language->slug);
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
