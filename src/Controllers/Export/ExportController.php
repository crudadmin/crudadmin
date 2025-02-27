<?php

namespace Admin\Controllers\Export;

use Admin\Helpers\SecureDownloader;
use Admin\Controllers\Crud\CRUDController;
use Storage;

class ExportController extends CRUDController
{
    public function export($table, $exportKey)
    {
        $model = $this->getModel($table);

        if ( !($export = $model->getAdminExport($exportKey)) ){
            abort(404);
        }

        if ( $export->save() === false ){
            abort(500);
        }

        $downloader = new SecureDownloader($export->basepath());

        return [
            'download_url' => $downloader->getDownloadPath(true),
        ];
    }
}