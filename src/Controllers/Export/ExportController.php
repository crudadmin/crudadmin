<?php

namespace Admin\Controllers\Export;

use Admin\Controllers\Controller;
use Admin\Controllers\Crud\CRUDController;
use Admin;

class ExportController extends CRUDController
{
    private function getBootedModel($table, $access = null)
    {
        $model = $this->getModel($table, false);

        if ( admin()->hasAccess($model, $access) === false ){
            return autoAjax()->error('Unauthorized.', 401)->throw();
        }

        return $model
            ->withExportResponse()
            ->bootExportResponse(
                request()->only([
                    'columns', '_columns',
                    'with', '_with',
                    'where', '_where',
                    'scope', '_scope',
                ])
            );
    }
    public function rows($table)
    {
        $rows = $this->getBootedModel($table, 'read')->paginate(request('limit'));

        $rows->getCollection()->each->setFullExportResponse();

        return autoAjax()->data([
            'pagination' => $rows,
        ]);
    }

    public function show($table, $id)
    {
        $row = $this->getBootedModel($table, 'read')
                    ->where(request('_selector', request('selector', 'id')), $id)->firstOrFail();

        return autoAjax()->data([
            'row' => $row->setFullExportResponse()
        ]);
    }

    public function update($table, $id)
    {
        $query = $this->getBootedModel($table, 'update');

        $row = $query->where(request('_selector', request('selector', 'id')), $id)->firstOrFail();

        $data = $row->validator()->only(
            array_intersect($query->getModel()->getFillable(), request()->keys())
        )->validate()->getData();

        $row->update($data);

        return autoAjax()->success(_('Zmeny boli úspešne uložené.'))->data([
            'row' => $row->setFullExportResponse()
        ]);
    }

    public function models()
    {
        return collect(Admin::getAdminModels())
                ->filter(function($model){
                    return admin()->hasAccess($model);
                })->map(function($model){
                    return [
                        'name' => $model->getProperty('name'),
                        'table' => $model->getTable(),
                    ];
                })
                ->values();
    }

    public function scheme($table)
    {
        $model = $this->getModel($table, false);

        return view('admin::openapi.pagination_scheme', compact('model'));
    }
}
