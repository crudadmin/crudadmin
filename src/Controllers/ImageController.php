<?php

namespace Gogol\Admin\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Gogol\Admin\Helpers\File;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Image;
use Admin;

class ImageController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin', [ 'only' => 'getThumbnail' ]);
    }

    /*
     * Returns image response of file
     */
    public function getThumbnail($model, $field, $file)
    {
        $file = File::adminModelFile($model, $field, $file);

        //Check if model and field exists
        if ( ($model = Admin::getModelByTable($model)) && $model->getField($field) )
        {
            return response()->download( $file->resize(40, 40, 'admin-thumbnails', true, false)->path );
        }

        return abort(404);
    }

    /*
     * Resize image which has not been resized yet
     */
    public function resizeImage($a = null, $b = null, $c = null, $d = null, $e = null)
    {
        $filepath = File::adminModelCachePath(implode('/', array_filter(func_get_args())));

        $temporary_path = $filepath . '.temp';

        try {
            //Get resizing information from cache
            $cache = @json_decode(@file_get_contents($temporary_path), true);

            //If not exists any form of file
            if ( !@$cache['original_path'] || ! file_exists($cache['original_path']) || ! file_exists($temporary_path) ) {
                abort(404);
            }

            //Resize image
            $file = (new File($cache['original_path']))->image($cache['mutators'], null, true, true);

            //Remove temporary file with settings
            @unlink($temporary_path);
        } catch (\Exception $e) {
            abort(404);
        }

        //Return resized image response
        return $file->response();
    }
}