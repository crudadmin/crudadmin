<?php

namespace Admin\Controllers\Api;

use Admin;
use Admin\Controllers\Crud\CRUDController;
use Symfony\Component\Yaml\Yaml;

class ApiController extends CRUDController
{
    private function getBootedModel($table, $access = true)
    {
        $model = $this->getModel($table, false);

        if ( admin()->hasAccess($model, $access) === false ){
            return autoAjax()->error('Unauthorized - '.$model->getProperty('name').($access ? ' - '.$access : '').'.', 401)->throw();
        }

        return $model
            ->withAdminApiResponse()
            ->bootExportResponse(
                request()->only([
                    'columns', '_columns',
                    'with', '_with',
                    'where', '_where',
                    'scope', '_scope',
                ])
            );
    }

    private function logRequest()
    {
        if ( config('admin.api.logging', true) == false ){
            return false;
        }

        $data = json_encode([
            'method' => request()->getMethod(),
            'user_id' => admin()->getKey(),
            'path' => request()->getPathInfo(),
            'get' => request()->input(),
            'post' => request()->post(),
        ]);

        Admin::log()->info($data);
    }

    public function rows($table)
    {
        $this->logRequest();

        $limit = request('limit');
        $rows = $this->getBootedModel($table, 'read');

        // Unlimited response
        if ( is_null($limit) === false && ($limit == 0 || $limit == -1) ) {
            $pagination = [
                'current_page' => 1,
                'data' => $rows->get()->each->setFullExportResponse(),
                'from' => 1,
                'to' => $total = $rows->count(),
                'last_page' => 1,
                'per_page' => $total,
                'total' => $total,
            ];
        }

        // Paginated response
        else {
            $pagination = $rows->paginate(request('limit'));
            $pagination->getCollection()->each->setFullExportResponse();
        }

        return autoAjax()->data([
            'pagination' => $pagination,
        ]);
    }

    public function show($table, $id)
    {
        $this->logRequest();

        $row = $this->getBootedModel($table, 'read')
                    ->where(request('_selector', request('selector', 'id')), $id)->firstOrFail();

        return autoAjax()->data([
            'row' => $row->setFullExportResponse()
        ]);
    }

    public function create($table)
    {
        $this->logRequest();

        $model = $this->getBootedModel($table, 'insert')->getModel();

        $data = $model->validator()
                    ->addDefaultValues()
                    ->validate()
                    ->getData();

        $row = $model->create($data);

        //Check for model rules after row is already saved/created
        $row->checkForModelRules(['created'], true);

        return autoAjax()->success(_('Záznam bol úspešne uložený.'))->data([
            'row' => $row->setFullExportResponse()
        ]);
    }

    public function update($table, $id)
    {
        $this->logRequest();

        $query = $this->getBootedModel($table, 'update');

        $row = $query->where(request('_selector', request('selector', 'id')), $id)->firstOrFail();

        $data = $row->validator()
                    ->only(
                        array_intersect($query->getModel()->getFillable(), request()->keys())
                    )
                    ->validate()
                    ->getData();

        $row->update($data);

        //Check for model rules after row is already updated
        $row->checkForModelRules(['updated'], true);

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
                        'relations' => collect($model->getAdminApiRelations())->pluck('table'),
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
