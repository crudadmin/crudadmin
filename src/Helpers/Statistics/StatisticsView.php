<?php

namespace Admin\Helpers\Statistics;

use Illuminate\Support\Facades\DB;

class StatisticsView
{
    public function groups()
    {
        return [
            'all' => [
                'name' => 'Všetci',
                'query' => function($query){
                    return $query;
                },
            ],
        ];
    }

    public function ranges()
    {
        return [
            'yearly' => [
                'name' => 'Ročne',
                'query' => function($query){
                    return $query->addSelect(DB::raw('COUNT(*) as value, YEAR(created_at) as `group`'))->groupByRaw('`group`');
                },
            ],
            'monthly' => [
                'name' => 'Mesačne',
                'query' => function($query){
                    return $query->addSelect(DB::raw('COUNT(*) as value, DATE_FORMAT(created_at, "%Y-%m") as `group`'))->groupByRaw('`group`');
                },
            ],
            'weekly' => [
                'name' => 'Týždenné',
                'query' => function($query){
                    return $query->addSelect(DB::raw('COUNT(*) as value, DATE_FORMAT(created_at, "%Y-%v") as `group`'))->groupByRaw('`group`');
                },
            ],
            'daily' => [
                'name' => 'Denné',
                'query' => function($query){
                    return $query->addSelect(DB::raw('COUNT(*) as value, DATE(created_at) as `group`'))->groupByRaw('`group`');
                },
            ],
        ];
    }
}