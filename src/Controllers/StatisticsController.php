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


        return autoAjax()->data(
            $statistics->toArray(
                request('group'),
                request('range'),
                request('scopes'),
                request('search')
            )
        );
    }
}