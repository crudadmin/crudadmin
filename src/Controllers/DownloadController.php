<?php

namespace Admin\Controllers;

use Admin\Helpers\File;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => 'signedDownload']);
    }

    public function getPath($file = null)
    {
        $file = request('file');
        $model = request('model');
        $field = request('field');

        $file = File::adminModelFile($model, $field, $file);

        $publicPath = public_path('uploads');
        $realPath = dirname(realpath($file->basepath));

        //Alow download only from uploads folder
        if ( substr($realPath, 0, strlen($publicPath)) != $publicPath ){
            abort(404);
        }

        //Protection
        if (! file_exists($file->path)) {
            abort(404, '<h1>404 - file not found...</h1>');
        }

        return $file->path;
    }

    /*
     * Returns download resposne of file
     */
    public function index($file = null)
    {
        return response()->download($this->getPath($file));
    }

    public function signedDownload($hash)
    {
        $path = request()->get('file');

        if ($hash != File::getHash($path)) {
            abort(404);
        }

        return $this->index($path);
    }
}
