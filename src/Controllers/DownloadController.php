<?php

namespace Admin\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Admin\Helpers\File;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Image;

class DownloadController extends Controller
{
    public function construct()
    {
        $this->muddleware('auth', [ 'except' => 'signedDownload' ]);
    }

    public function getPath($file = null)
    {
        $file = request('file');
        $model = request('model');
        $field = request('field');

        $file = File::adminModelFile($model, $field, $file);

        //Protection
        if ( ! file_exists( $file->path ) )
        {
            abort(404, '<h1>404 - file not found...</h1>');
        }

        return $file->path;
    }

    /*
     * Returns download resposne of file
     */
    public function index($file = null)
    {
        return response()->download( $this->getPath($file) );
    }

    public function signedDownload($hash)
    {
        $path = request()->get('file');

        if ( $hash != File::getHash($path))
            abort(404);

        return $this->index($path);
    }
}