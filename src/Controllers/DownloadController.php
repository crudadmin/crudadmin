<?php

namespace Gogol\Admin\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Gogol\Admin\Helpers\File;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Image;

class DownloadController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin', [
            'except' => 'signedDownload'
        ]);
    }

    public function getPath()
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
        if ( ! file_exists( $file->basepath ) ) {
            abort(404, '<h1>404 - file not found...</h1>');
        }

        return $file->basepath;
    }

    /*
     * Returns download resposne of file
     */
    public function index()
    {
        $file = $this->getPath();

        return response()->download($file);
    }

    public function signedDownload($hash)
    {
        $path = implode('/', [request('model'), request('field'), request('file')]);

        if ( $hash != File::getHash($path)){
            abort(404);
        }

        return $this->index();
    }
}