<?php

namespace Admin\Controllers;

use Admin;
use Gettext;
use Illuminate\Http\Request;

class GettextController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $translations = Gettext::getJSTranslations(request('lang'));

        $js = view('admin::partials.gettext-translates', compact('translations'))->render();

        return response($js)->withHeaders([
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'max-age=2592000,public',
        ]);
    }

    /*
     * Return all translations for specifics language
     */
    public function getTranslations($id)
    {
        $language = Admin::getModel('Language')->findOrFail($id);

        $translations = Gettext::getTranslations($language);

        return response()->json($translations);
    }

    /*
     * Update translations for specific language
     */
    public function updateTranslations($id)
    {
        $language = Admin::getModel('Language')->findOrFail($id);

        $changes = json_decode(request('changes'));

        Gettext::updateTranslations($language, $changes);
    }
}
