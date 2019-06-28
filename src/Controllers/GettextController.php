<?php

namespace Admin\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Gettext;

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
}