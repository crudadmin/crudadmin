<?php

namespace Admin\Controllers;

use Admin\Helpers\File;
use Admin\Helpers\SecureDownloader;
use Illuminate\Http\Request;
use Admin;

class DownloadController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin', ['except' => 'signedDownload']);
    }

    /**
     * Build admin file from request query url
     *
     * @return  AdminModel
     */
    private function getFileFromRequest()
    {
        if ( !($model = Admin::getModelByTable(request('model'))) ){
            abort(404);
        }

        $fieldKey = basename(request('field'));
        $filename = basename(request('file'));

        return $model->getAdminFile($fieldKey, $filename);
    }

    /**
     * Downloading for authenticated administrators with permissions
     *
     * @return  Response
     */
    public function adminDownload()
    {
        $adminFile = $this->getFileFromRequest();

        $model = Admin::getModelByTable(request('model'));

        //Check if admin has permissions to given model
        if ( admin()->hasAccess($model) == false ) {
            abort(401);
        }

        //Protection
        if ( $adminFile->exists === false ) {
            abort(404, '<h1>404 - file not found...</h1>');
        }

        return $adminFile->downloadResponse();
    }

    /**
     * Signed downloading for authenticated administrators with permissions
     *
     * @return  Response
     */
    public function securedAdminDownload()
    {
        $hash = request('hash');

        if ( !($path = SecureDownloader::getSessionBasePath($hash)) ){
            return abort(404);
        }

        $data = SecureDownloader::getSessionBaseData($hash);

        $response = response()->download($path);

        if ( @$data['delete'] === true ){
            $response->deleteFileAfterSend();
        }

        return $response;
    }

    /**
     * Download file with signed hash for guests
     *
     * @param  string  $hash
     *
     * @return  Response
     */
    public function signedDownload($hash)
    {
        $adminFile = $this->getFileFromRequest();

        //Check file model hashes
        if ( $hash != $adminFile->hash || $adminFile->exists === false ){
            abort(404);
        }

        return $adminFile->downloadResponse();
    }
}
