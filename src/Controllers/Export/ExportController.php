<?php

namespace Admin\Controllers\Export;

use Admin\Controllers\Controller;
use Admin\Controllers\Crud\CRUDController;

class ExportController extends CRUDController
{
    private function getBootedModel($table)
    {
        $model = $this->getModel($table, false);

        return $model
            ->withExportResponse()
            ->bootExportResponse(
                request()->only(['columns', '_columns', 'with', '_with'])
            );
    }
    public function rows($table)
    {
        $rows = $this->getBootedModel($table)->paginate(request('limit'));

        $rows->getCollection()->each->setExportResponse();

        return autoAjax()->data([
            'pagination' => $rows,
        ]);
    }

    public function show($table, $id)
    {
        $row = $this->getBootedModel($table)->where(request('_selector', request('selector', 'id')), $id)->firstOrFail();

        return autoAjax()->data([
            'row' => $row->setExportResponse()
        ]);
    }

    public function update($table, $id)
    {
        $query = $this->getBootedModel($table);

        $row = $query->where(request('_selector', request('selector', 'id')), $id)->firstOrFail();

        $data = $row->validator()->only(
            array_intersect($query->getModel()->getFillable(), request()->keys())
        )->validate()->getData();

        $row->update($data);

        return autoAjax()->success(_('Zmeny boli úspešne uložené.'))->data([
            'row' => $row->setExportResponse()
        ]);
    }

    public function scheme($table)
    {
        $model = $this->getModel($table, false);

        return view('admin::openapi.pagination_scheme', compact('model'));
    }
}
