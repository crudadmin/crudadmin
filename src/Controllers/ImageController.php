<?php

namespace Admin\Controllers;

use Admin;
use Image;
use Admin\Helpers\File;

class ImageController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin', ['only' => 'getThumbnail']);
    }

    /*
     * Returns image response of file
     */
    public function getThumbnail($model, $field, $file)
    {
        $file = File::adminModelFile($model, $field, $file);

        //Check if model and field exists
        if (($model = Admin::getModelByTable($model)) && $model->getField($field) && $file->exists()) {
            return response()->download($file->resize(40, 40, 'admin-thumbnails', true, false)->path);
        }

        return abort(404);
    }

    /*
     * Resize image which has not been resized yet
     */
    public function resizeImage($a = null, $b = null, $c = null, $d = null, $e = null)
    {
        $filepath = File::adminModelCachePath(implode('/', array_filter(func_get_args())));

        $temporary_path = $filepath.'.temp';

        //If not exists any form of file
        if (! file_exists($filepath) || ! file_exists($temporary_path)) {
            abort(404);
        }

        //Get resizing information from cache
        $cache = json_decode(file_get_contents($temporary_path), true);

        //Resize image
        $file = (new File($cache['original_path']))->image($cache['mutators'], null, true, true);

        //Remove temporary file with settings
        @unlink($temporary_path);

        //Return resized image response
        return $file->response();
    }
}
