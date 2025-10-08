<?php

namespace Admin\Helpers\Statistics;

use Admin;
use Admin\Helpers\AdminRowsSearch;
use Illuminate\Support\Facades\DB;
use AdminTree;

class StatisticsView
{
    /**
     * View creation date
     *
     * @var mixed
     */
    public $date;

    /**
     * Stats table
     *
     * @var mixed
     */
    public $table;

    /**
     * Should be autoloaded?
     *
     * @var bool
     */
    public $active = true;

    /**
     * Default range
     *
     * @var string
     */
    public $range = 'monthly';

    /**
     * Default filter
     *
     * @var undefined
     */
    public $filter = null;

    /**
     * Has date filters?
     *
     * @var bool
     */
    public $filters = true;

    /**
     * Is search enabled
     *
     * @var bool
     */
    public $search = true;

    /**
     * Are filters enabled
     *
     * @var bool
     */
    public $autoDatasetsFromStates = true;

    /**
     * How value should be selected
     *
     * @var string
     */
    public $selector = 'COUNT(*) AS `value`';

    /**
     * Additional chart settings
     *
     * @var string
     */
    public $settings = [
        'unit' => '',
    ];

    /**
     * Returns model
     *
     * @return void
     */
    public function model()
    {
        return Admin::getModelByTable($this->table);
    }

    /**
     * Builds base query for all datasets
     *
     * @return void
     */
    public function query($query)
    {
        return $query;
    }

    /**
     * Datasets displayed on each fetch in graph
     *
     * @return void
     */
    public function datasets()
    {
        if ( $this->hasFilterStates() ) {
            return $this->getStatesDatasets();
        }

        return [
            [
                'name' => _('Všetci'),
                'color' => 'primary',
                'query' => function($query){
                    return $query->selectRaw($this->selector());
                },
            ]
        ];
    }

    /**
     * Filters which we may applie during data fetching
     *
     * @return void
     */
    public function filters()
    {
        return array_merge([
            'all' => [
                'name' => _('Všetko'),
                'query' => function($query){
                    return $query;
                },
            ],
        ], $this->filters ? [
            'last_month' => [
                'name' => _('30 dní'),
                'query' => function($query){
                    return $query->where($query->qualifyColumn('created_at'), '>=', now()->subMonth()->startOfDay());
                },
            ],
            'last_week' => [
                'name' => _('7 dní'),
                'query' => function($query){
                    return $query->where($query->qualifyColumn('created_at'), '>=', now()->subWeek()->startOfDay());
                },
            ],
        ] : []);
    }

    /**
     * Available data ranges to group data by
     *
     * @return void
     */
    public function ranges()
    {
        return [
            'yearly' => [
                'name' => _('Ročne'),
                'group_format' => 'Y',
                'label_format' => 'Y',
                'unit' => 'year',
                'query' => function($query){
                    return $query->selectRaw('YEAR('.$query->qualifyColumn('created_at').') as `group`')->groupByRaw('`group`');
                },
            ],
            'monthly' => [
                'name' => _('Mesačne'),
                'group_format' => 'Y-m',
                'label_format' => 'MMM Y',
                'unit' => 'month',
                'query' => function($query){
                    return $query->selectRaw('DATE_FORMAT('.$query->qualifyColumn('created_at').', "%Y-%m") as `group`')->groupByRaw('`group`');
                },
            ],
            'weekly' => [
                'name' => _('Týždenné'),
                'group_format' => 'Y-v',
                'label_format' => 'ww. Y',
                'unit' => 'week',
                'query' => function($query){
                    return $query->selectRaw('DATE_FORMAT('.$query->qualifyColumn('created_at').', "%Y-%v") as `group`')->groupByRaw('`group`');
                },
            ],
            'daily' => [
                'name' => _('Denné'),
                'group_format' => 'Y-m-d',
                'label_format' => 'DD. MM. YYYY',
                'unit' => 'day',
                'query' => function($query){
                    return $query->selectRaw('DATE('.$query->qualifyColumn('created_at').') as `group`')->groupByRaw('`group`');
                },
            ],
        ];
    }

    public function toArray($filter, $range, $scopes = [], $search = [])
    {
        $filter = $filter ?: $this->filter ?: array_keys($this->filters())[0];
        $range = $range ?: $this->range ?: array_keys($this->ranges())[0];

        return [
            'title' => $this->title(),
            'model' => [
                'table' => $this->table,
                'fields' => request('initial') ? AdminTree::getModelFields($this->model(), true) : [],
            ],
            'has' => [
                'search' => $this->search,
                'autoDatasetsFromStates' => $this->autoDatasetsFromStates,
            ],
            'scopes' => [
                'filters' => [
                    'key' => $filter,
                    'list' => $this->filters(),
                ],
                'ranges' => [
                    'key' => $range,
                    'list' => $this->ranges(),
                ],
            ],
            'datasets' => $this->getData($filter, $range, $scopes, $search),
        ];
    }

    /**
     * Returns data from queries
     *
     * @param  mixed $filter
     * @param  mixed $range
     * @param  mixed $scopes
     * @param  mixed $search
     * @return void
     */
    private function getData($filter, $range, $scopes, $search)
    {
        return collect($this->datasets())->map(function($group) use ($filter, $range, $scopes, $search){
            $query = $this->query(
                $this->model()->query()
            );

            $query = $group['query']($query);
            $query = $this->filters()[$filter]['query']($query);
            $query = $this->ranges()[$range]['query']($query);
            $query = $query->filterByScopes($scopes);

            //Search in rows
            (new AdminRowsSearch($this->model(), $query, $search))->filter();

            $group['settings'] = $group['settings'] ?? $this->settings ?: [];
            $group['data'] = $query->get();

            return $group;
        });
    }

    /**
     * Has model filter states enable
     *
     * @return void
     */
    private function hasFilterStates()
    {
        $model = $this->model();

        return $this->autoDatasetsFromStates && method_exists($model, 'getFilterStates') && count($model->getFilterStates()) > 0;
    }

    /**
     * Get datasets from filter states
     *
     * @return void
     */
    private function getStatesDatasets()
    {
        return array_map(function($state){
            return [
                ...$state,
                'query' => function($query) use ($state) {
                    return $query->where($state['query'])->selectRaw($this->selector());
                },
            ];
        }, $this->model()->getFilterStates());
    }

    /**
     * Returns value selector for statistics
     *
     * @return void
     */
    private function selector()
    {
        return $this->selector;
    }
}