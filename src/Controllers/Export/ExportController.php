<?php

namespace Admin\Controllers\Export;

use Admin;
use Admin\Controllers\Controller;
use Admin\Controllers\Crud\CRUDController;
use Symfony\Component\Yaml\Yaml;

class ExportController extends CRUDController
{
    private function getBootedModel($table, $access = true)
    {
        $model = $this->getModel($table, false);

        if ( admin()->hasAccess($model, $access) === false ){
            return autoAjax()->error('Unauthorized - '.$model->getProperty('name').($access ? ' - '.$access : '').'.', 401)->throw();
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
        $models = collect(Admin::getAdminModels())
                ->filter(function($model){
                    return admin()->hasAccess($model);
                });

        return $models->keyBy(function($model){
            return $model->getTable();
        })->map(function($model){
                    return [
                        'name' => $model->getProperty('name'),
                        'relations' => collect($model->getExportRelations())->pluck('table'),
                    ];
                });
    }

    public function swagger()
    {
        return view('admin::openapi.swagger');
    }

    public function openApiScheme($type, $table = null)
    {
        $yamlString = $this->scheme($table)->render();

        if ( $type == 'json' ) {
            $yaml = Yaml::parse($yamlString);

            return $yaml;
        } else {
            return $yamlString;
        }
    }

    public function scheme($table = null)
    {
        $tables = collect(array_filter(array_merge(array_wrap($table), explode(',', request('models', '')))));

        if ( count($tables) == 0 ){
            $models = Admin::getAdminModels();

            $tables = collect($models)->filter(function($model){
                return admin()->hasAccess($model, true);
            })->map(function($model){
                return $model->getTable();
            });
        }

        $models = $tables->map(function($table){
            return $this->getBootedModel($table)->getModel();
        })->keyBy(function($model){
            return $model->getTable();
        });

        return view('admin::openapi.pagination_scheme', compact('models'));
    }
}
