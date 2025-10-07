<?php

namespace Admin\Helpers\Statistics;

use Admin;
use Admin\Helpers\AdminRowsSearch;
use Illuminate\Support\Facades\DB;
use AdminTree;

class StatisticsView
{
    /**
     * Stats table
     *
     * @var mixed
     */
    public $table;

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
    public $filters = true;

    public function model()
    {
        return Admin::getModelByTable($this->table);
    }

    public function query()
    {
        return $this->model()->query();
    }

    public function filters()
    {
        return [
            'all' => [
                'name' => _('Všetko'),
                'color' => 'primary',
                'query' => function($query){
                    return $query;
                },
            ],
            // 'last_month' => [
            //     'name' => _('Posledný mesiac'),
            //     'query' => function($query){
            //         return $query->where($query->qualifyColumn('created_at'), '>=', now()->subMonth());
            //     },
            // ],
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

    public function value($query)
    {
        return $query->selectRaw('COUNT(*) as `value`')->get();
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
                'filters' => $this->filters,
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

    public function getData($filter, $range, $scopes, $search)
    {
        return collect($this->datasets())->map(function($group) use ($filter, $range, $scopes, $search){
            $query = $this->query();

            $query = $this->filters()[$filter]['query']($query);
            $query = $this->ranges()[$range]['query']($query);
            $query = $query->filterByScopes($scopes);

            //Search in rows
            (new AdminRowsSearch($this->model(), $query, $search))->filter();

            $group['data'] = $this->value($query);
            return $group;
        });
    }
}