<?php

namespace Admin\Controllers\Export;

use Admin\Helpers\SecureDownloader;
use Admin\Controllers\Crud\CRUDController;

class ExportController extends CRUDController
{
    public function export($table, $exportKey)
    {
        $model = $this->getModel($table);
        $isDebug = request()->get('debug') == 1;

        if ( !($export = $model->getAdminExport($exportKey)) ){
            abort(404);
        }

        // Display debug output
        if ( $isDebug ) {
            return response($export->html(), 200, [
                'Content-Type' => 'text/html',
            ]);
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