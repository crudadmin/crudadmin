<?php

namespace Gogol\Admin\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DownloadController extends Controller
{
    public function index()
    {
        $file = str_replace('..', '', request()->get('file'));
        $array = explode('/', $file);

        $path = public_path( 'uploads/' . $file );

        //Protection
        if ( ! file_exists( $path ) || count($array) != 3 || !file_exists(public_path('uploads/'.$array[0])) || !file_exists(public_path('uploads/'.$array[0].'/'.$array[1])) )
        {
            return '<h1>404 - file not found...</h1>';
        }

        return response()->download( $path );
    }
}