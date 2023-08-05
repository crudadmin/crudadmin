<?php

namespace Admin\Controllers;

use Admin;
use Admin\Core\Helpers\Storage\AdminFile;
use Cache;
use Exception;
use Illuminate\Http\Response;
use Image;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\UnableToRetrieveMetadata;

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

        //Private files access
        if ( $model->hasFileAccess($fieldKey) === false ){
            abort(401);
        }

        $adminFile = $model->getAdminFile($fieldKey, $filename);

        $storage = $adminFile->getCacheStorage();

        $resizedImage = $adminFile->resize(50, 50, true);

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

        //Encrypted images should be removed on completed response
        if ( $resizedImage && $adminFile->isEncrypted() ){
            $resizedImage->remove();
        }
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
        } catch (UnableToRetrieveMetadata $e){
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
        //Unknown model
        if ( !($model = Admin::getModelByTable($table)) ){
            abort(404);
        }

        //Private files access
        if ( $model->hasFileAccess($fieldKey) === false ){
            abort(401);
        }

        $adminFile = $model->getAdminFile($fieldKey, $filename);

        $extension = strtolower($adminFile->extension);

        if ( in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])){
            $storage = $adminFile->getStorage();

            try {
                if ( $adminFile->isEncrypted() ) {
                    $response = Image::make($adminFile->get())->response();
                } else {
                    $response = $storage->response($adminFile->path);
                }

                //Retrieve resized and compressed image
                $response->setMaxAge(3600 * 24 * 365)->setPublic();

                //Send response manually, because we does not want to throw cookies etc..
                $response->send();
            } catch (FileNotFoundException $e){
                abort(404);
            } catch (UnableToRetrieveMetadata $e){
                abort(404);
            } catch (Exception $e){
                abort(500);
            }
        } else if ( in_array($extension, ['pdf']) ) {
            return response()->make($adminFile->get(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$adminFile->filename.'"'
            ]);
        }

        return $adminFile->downloadResponse();
    }
}
