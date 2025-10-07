<?php

namespace Admin\Controllers;

use Admin\Controllers\Controller;

class StatisticsController extends Controller
{
    public function show($key)
    {
        $classname = 'App\Admin\Statistics\\'.ucfirst($key);

        if ( !class_exists($classname) ){
            abort(404);
        }

        $statistics = new $classname();

        $model = $statistics->model();

        if ( is_null($model) ) {
            return autoAjax()->error('Model does not exists for table '.$statistics->table, 500);
        }

        if ( !admin()->hasAccess($model, 'read') ) {
            return autoAjax()->permissionsError($model->getTable(), 403);
        }

        return autoAjax()->data(
            $statistics->toArray(
                request('filter'),
                request('range'),
                request('scopes'),
                request('search')
            )
        );
    }
}