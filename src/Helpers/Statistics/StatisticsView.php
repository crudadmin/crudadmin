<?php

namespace Admin\Helpers\Statistics;

use Illuminate\Support\Facades\DB;

class StatisticsView
{
    /**
     * Default range
     *
     * @var string
     */
    public $range = 'monthly';

    /**
     * Default group
     *
     * @var undefined
     */
    public $group = null;

    public function groups()
    {
        return [
            'all' => [
                'name' => _('Všetci'),
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
                'name' => _('Ročne'),
                'group_format' => 'Y',
                'label_format' => 'Y',
                'unit' => 'year',
                'query' => function($query){
                    return $query->addSelect(DB::raw('COUNT(*) as value, YEAR(created_at) as `group`'))->groupByRaw('`group`');
                },
            ],
            'monthly' => [
                'name' => _('Mesačne'),
                'group_format' => 'Y-m',
                'label_format' => 'MMM Y',
                'unit' => 'month',
                'query' => function($query){
                    return $query->addSelect(DB::raw('COUNT(*) as value, DATE_FORMAT(created_at, "%Y-%m") as `group`'))->groupByRaw('`group`');
                },
            ],
            'weekly' => [
                'name' => _('Týždenné'),
                'group_format' => 'Y-v',
                'label_format' => 'ww. Y',
                'unit' => 'week',
                'query' => function($query){
                    return $query->addSelect(DB::raw('COUNT(*) as value, DATE_FORMAT(created_at, "%Y-%v") as `group`'))->groupByRaw('`group`');
                },
            ],
            'daily' => [
                'name' => _('Denné'),
                'group_format' => 'Y-m-d',
                'label_format' => 'DD. MM. YYYY',
                'unit' => 'day',
                'query' => function($query){
                    return $query->addSelect(DB::raw('COUNT(*) as value, DATE(created_at) as `group`'))->groupByRaw('`group`');
                },
            ],
        ];
    }
}