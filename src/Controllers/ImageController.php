<?php

namespace Admin\Controllers;

use Admin\Core\Helpers\Storage\AdminFile;
use Admin;
use Cache;

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
    public function resizeImage($table, $fieldKey, $prefix, $filename)
    {
        if ( !($model = Admin::getModelByTable($table)) ){
            abort(404);
        }

        $adminFile = $model->getAdminFile($fieldKey, $filename);

        //If does not exists cache path, but also does not exists cached image already
        //But alsot if cached image exists, and temorary path does not exists
        if ( ! $resizeData = $adminFile->getCachedResizeData($prefix) ) {
            abort(404);
        }

        //Resize image
        $resizedImage = $adminFile->image($resizeData['mutators'] ?? [], true);

        $storage = $resizedImage->getStorage();

        //Retrieve resized and compressed image
        $response = $storage->response($resizedImage->path)
            ->setMaxAge(3600 * 24 * 365)
            ->setPublic();

        //Send response manually, because we does not want to throw cookies etc..
        $response->send();
    }
}
