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

        $groups = $statistics->groups();
        $ranges = $statistics->ranges();

        $group = request('group', array_keys($groups)[0]);
        $range = request('range', array_keys($ranges)[0]);

        $query = $statistics->query();

        $query = $groups[$group]['query']($query);
        $query = $ranges[$range]['query']($query);

        $data = $statistics->value($query);

        return autoAjax()->data([
            'title' => $statistics->title(),
            'group' => $group,
            'range' => $range,
            'data' => $data,
            'groups' => $groups,
            'ranges' => $ranges,
        ]);
    }
}