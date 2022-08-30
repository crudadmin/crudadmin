<?php

namespace Admin\Controllers\Export;

use Admin\Controllers\Controller;
use Admin\Controllers\Crud\CRUDController;

class ExportController extends CRUDController
{
    public function rows($table)
    {
        $model = $this->getModel($table, false);

        $rows = $model
            ->withExportResponse()
            ->bootExportResponse(
                request()->only(['columns', 'with'])
            )
            ->paginate(request('limit'));

        $rows->getCollection()->each->setExportResponse();

        return [
            'pagination' => $rows,
        ];
    }

    public function scheme($table)
    {
        $model = $this->getModel($table, false);

        return view('admin::openapi.pagination_scheme', compact('model'));
    }
}