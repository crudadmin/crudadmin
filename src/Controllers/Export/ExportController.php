<?php

namespace Admin\Controllers\Export;

use Admin\Controllers\Controller;
use Admin\Controllers\Crud\CRUDController;

class ExportController extends CRUDController
{
    public function rows($model)
    {
        $model = $this->getModel($model, false);

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
}
