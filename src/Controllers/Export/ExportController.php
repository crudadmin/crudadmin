<?php

namespace Admin\Controllers\Export;

use Admin\Controllers\Crud\CRUDController;

class ExportController extends CRUDController
{
    public function export($table, $exportKey)
    {
        $model = $this->getModel($table);

        if ( !($export = $model->getAdminExport($exportKey)) ){
            abort(404);
        }

        dd($export);

        return $export->export();
    }
}