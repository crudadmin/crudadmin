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
        $cacheFilePath = File::adminModelCachePath(implode('/', array_filter(func_get_args())));

        $temporaryPath = File::getTemporaryFilename($cacheFilePath);

        //If does not exists cache path, but also does not exists cached image already
        //But alsot if cached image exists, and temorary path does not exists
        if (
            ! file_exists($cacheFilePath) && ! file_exists($temporaryPath)
            || ! file_exists($temporaryPath)
        ) {
            abort(404);
        }

        //Get resizing information from cache
        $cache = json_decode(file_get_contents($temporaryPath), true);

        //Resize image
        $file = (new File(public_path($cache['original_path'])))->image(
            $cache['mutators'] ?? null,
            $cache['directory'] ?? null,
            true,
            true
        );

        //Remove temporary file with settings
        @unlink($temporaryPath);

        //Return resized image response
        return $file->response();
    }
}
