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

        $group = request('group');
        $range = request('range');

        return autoAjax()->data(
            $statistics->toArray($group, $range)
        );
    }
}