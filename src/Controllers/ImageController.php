<?php

namespace Admin\Controllers;

use Admin;
use Admin\Core\Helpers\Storage\AdminFile;
use Cache;
use Exception;
use League\Flysystem\FileNotFoundException;

class ImageController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin', ['only' => 'getThumbnail']);
    }

    /*
     * Returns image response of file
     */
    public function getThumbnail($table, $fieldKey, $filename)
    {
        if ( !($model = Admin::getModelByTable($table)) ){
            abort(404);
        }

        $adminFile = $model->getAdminFile($fieldKey, $filename);

        //Check if model and field exists
        if ( $adminFile->exists == false ) {
            return abort(404);
        }

        $storage = $adminFile->getStorage();

        //Retrieve resized and compressed image
        $response = $storage->response(
                $adminFile->resize(50, 50, true)->path,
                200,
                ['CrudAdmin' => 'Image-Resizer']
            )
            ->setMaxAge(3600 * 24 * 365)
            ->setPublic();

        //Send response manually, because we does not want to throw cookies etc..
        $response->send();
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

        //If is not local storage, and storage url is different than actual crudadmin renderer path
        //we can resize on final destination after resize.
        if (
            $adminFile->externalStorageResizer()
            && config('admin.resizer.redirect_after_resize', true) == true
            && $resizedImage->isLocalStorage() === false
            && $resizedImage->url !== url()->current()
        ) {
            return redirect($resizedImage->url, 301);
        }

        //Image should be returned as response
        try {
            $storage = $resizedImage->getCacheStorage();

            //Retrieve resized and compressed image
            $response = $storage->response(
                    $resizedImage->path,
                    200,
                    ['CrudAdmin' => 'Image-Resizer']
                )
                ->setMaxAge(3600 * 24 * 365)
                ->setPublic();

            //Send response manually, because we does not want to throw cookies etc..
            $response->send();
        } catch (FileNotFoundException $e){
            abort(404);
        } catch (Exception $e){
            abort(500);
        }
    }

    /*
     * Get files from cloud storage
     */
    public function getFile($table, $fieldKey, $filename)
    {
        if ( !($model = Admin::getModelByTable($table)) ){
            abort(404);
        }

        $adminFile = $model->getAdminFile($fieldKey, $filename);

        $storage = $adminFile->getStorage();

        //Retrieve resized and compressed image
        $response = $storage->response($adminFile->path)
            ->setMaxAge(3600 * 24 * 365)
            ->setPublic();

        //Send response manually, because we does not want to throw cookies etc..
        $response->send();
    }
}
