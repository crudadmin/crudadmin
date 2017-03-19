<?php

namespace Gogol\Admin\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Gogol\Admin\Helpers\File;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DownloadController extends Controller
{
    public function construct()
    {
        $this->muddleware('auth', [ 'except' => 'signedDownload' ]);
    }

    public function index($file = null)
    {
        if ( ! $file )
            $file = request()->get('file');

        $file = str_replace('..', '', $file);
        $array = explode('/', $file);

        $path = public_path( 'uploads/' . $file );

        //Protection
        if ( ! file_exists( $path ) || count($array) != 3 || !file_exists(public_path('uploads/'.$array[0])) || !file_exists(public_path('uploads/'.$array[0].'/'.$array[1])) )
        {
            return '<h1>404 - file not found...</h1>';
        }

        return response()->download( $path );
    }

    public function signedDownload($hash)
    {
        $path = request()->get('file');

        if ( $hash != File::getHash($path))
            abort(404);

        return $this->index($path);
    }
}