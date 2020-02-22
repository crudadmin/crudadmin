<?php

namespace Admin\Controllers;

use Admin;
use AdminLocalization;
use Admin\Helpers\Localization\LocalizationHelper;
use Facades\Admin\Helpers\Localization\JSTranslations;
use Gettext;
use Symfony\Component\HttpFoundation\Response;

class GettextController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  string  $localizationClass
     */
    public function index($localizationClass = 'Localization')
    {
        $translations = JSTranslations::getJSTranslations(request('lang'), $localizationClass::getModel());

        $js = view('admin::partials.gettext-translates', compact('translations'))->render();

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
     *
     * @return  [type]
     */
    public function adminIndex()
    {
        return $this->index(AdminLocalization::class);
    }

    public function getTranslationRow($id, $table)
    {
        return Admin::getModelByTable($table ?: 'languages')->findOrFail($id);
    }

    /*
     * Return all translations for specifics language
     */
    public function getTranslations($id, $table)
    {
        $language = $this->getTranslationRow($id, $table);

        $translations = JSTranslations::getTranslations($language);

        return response()->json($translations);
    }

    /*
     * Update translations for specific language
     */
    public function updateTranslations($id, $table)
    {
        $language = $this->getTranslationRow($id, $table);

        $changes = json_decode(request('changes'));

        JSTranslations::updateTranslations($language, $changes);
    }

    /*
     * Download updated poedit file
     */
    public function downloadTranslations($id, $table)
    {
        $language = $this->getTranslationRow($id, $table);

        JSTranslations::checkIfIsUpToDate($language);

        return response()->download($language->poedit_po->basepath);
    }
}
