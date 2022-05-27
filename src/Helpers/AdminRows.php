<?php

namespace Admin\Helpers;

use Admin;
use Admin\Eloquent\AdminModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminRows
{
    protected $model = null;
    protected $parentTable;
    protected $parentId;
    protected $limit;
    protected $page;
    protected $languageId;
    protected $scopes;

    /**
     * Class constructor
     *
     * @param  Admin\Eloquent\AdminModel  $model
     */
    public function __construct(AdminModel $model, $request = null)
    {
        $this->model = $model->getAdminRows();

        if ( $request ){
            $this->loadRequestParams($request);
        }
    }

    public function loadRequestParams($request)
    {
        $this->parentTable = $request['parentTable'] ?? null;
        $this->parentId = $request['parentId'] ?? null;
        $this->limit = (int)$request['limit'];
        $this->page = (int)$request['page'];
        $this->languageId = (int)$request['language_id'];
        $this->scopes = $request['scopes'];
    }

    public function setPage($page)
    {
        $this->page = $page;

        return $this;
    }

    private function isPrimaryKey($column, $columns)
    {
        if (in_array($column, ['id'])) {
            return true;
        }

        //If is correct relationship id
        if (count($columns) == 1) {
            if ($this->model->hasFieldParam($column, 'belongsToMany')) {
                return false;
            }

            if ($this->model->hasFieldParam($column, 'belongsTo')) {
                return true;
            }

            //If is select, but not multiple
            if ($this->isSelectColumn($column)) {
                return true;
            }
        }

        return false;
    }

    private function isSelectColumn($column)
    {
        return $column && $this->model->isFieldType($column, ['select', 'radio']) && ! $this->model->hasFieldParam($column, 'multiple');
    }

    private function isDateColumn($column)
    {
        if (in_array($column, ['created_at'])) {
            return true;
        }

        return $column && $this->model->isFieldType($column, ['date', 'datetime', 'time']);
    }

    private function getDateFormat($column, $value)
    {
        try {
            $field = $this->model->getField($column);

            $fromFormat = (isset($field['date_format']) ? $field['date_format'] : '') ?: 'd.m.Y';
            $fromFormat = @explode(' ', $fromFormat)[0];

            return Carbon::createFromFormat($fromFormat, $value);
        } catch (\Exception $e) {
            return;
        }
    }

    /*
     * Apply multi-text search scope for given query
     */
    protected function checkForSearching($query)
    {
        $search = request('search');

        if ( !is_array($search) || count($search) == 0 ){
            return $query;
        }

        foreach ($search as $item) {
            $itemQuery = $item['query'] ?? null;
            $itemQueryTo = $item['query_to'] ?? null;
            $column = $item['column'] ?? null;

            $query->where(function($query) use ($itemQuery, $itemQueryTo, $column) {
                $search = trim(preg_replace("/(\s+)/", ' ', str_replace('%', '', $itemQuery)));
                $search_to = trim(preg_replace("/(\s+)/", ' ', str_replace('%', '', $itemQueryTo)));

                if ($this->isDateColumn($column)) {
                    if ($itemQuery) {
                        $date = $this->getDateFormat($column, $search);
                    }

                    if ($itemQueryTo) {
                        $date_to = $this->getDateFormat($column, $search_to);
                    }

                    if (isset($date) && ! isset($date_to)) {
                        $query->whereDate($column, $date->format('Y-m-d'));
                    }

                    if (! isset($date) && isset($date_to)) {
                        $query->whereDate($column, '<=', $date_to->format('Y-m-d'));
                    }

                    if (isset($date) && isset($date_to)) {
                        $query->whereDate($column, '>=', $date->format('Y-m-d'))
                              ->whereDate($column, '<=', $date_to->format('Y-m-d'));
                    }

                    if (! isset($date) && ! isset($date_to)) {
                        $query->whereRaw('0');
                    }
                }

                //If is more than 3 chars for searching
                elseif (strlen($search) >= 3 || ($this->isSelectColumn($column) || is_numeric($search)) || $search_to) {
                    $columns = array_merge(array_keys($this->model->getFields()), ['id']);
                    $queries = explode(' ', $search);

                    //If is valid column
                    if (in_array($column, $columns)) {
                        $columns = [$column];
                    }

                    //Search scope
                    $query->where(function ($builder) use ($columns, $queries, $search, $search_to) {
                        foreach ($columns as $key => $column) {
                            //Search in all columns
                            $builder->{ $key == 0 ? 'where' : 'orWhere' }(function ($builder) use ($columns, $column, $queries, $search, $search_to) {
                                $tableColumn = $this->model->fixAmbiguousColumn($column);

                                //If is imaginarry field, skip whole process
                                if ( $this->model->isFieldType($column, 'imaginary') || $this->model->hasFieldParam($column, 'imaginary') ) {
                                    return;
                                } elseif ($search_to) {
                                    $builder->where(function ($builder) use ($column, $search, $search_to, $tableColumn) {
                                        if (! isset($search) && isset($search_to)) {
                                            $builder->where($tableColumn, '<=', $search_to);
                                        }

                                        if (isset($search) && isset($search_to)) {
                                            $builder->where($tableColumn, '>=', $search)
                                                    ->where($tableColumn, '<=', $search_to);
                                        }
                                    });
                                }

                                //Find exact id, value
                                elseif ($this->isPrimaryKey($column, $columns)) {
                                    foreach ($queries as $query) {
                                        $builder->where($tableColumn, $query);
                                    }
                                }

                                //Find by data in relation
                                elseif ($this->model->hasFieldParam($column, 'belongsTo')) {
                                    $relation = explode(',', $this->model->getField($column)['belongsTo']);

                                    $byColumns = $this->getNamesBuilder($relation, $columns);

                                    //We does not have columns for filter
                                    if ( count($byColumns) == 0 ){
                                        return;
                                    }

                                    $builder->orWhereHas(trim_end($column, '_id'), function ($builder) use ($byColumns, $relation, $queries) {
                                        foreach ($queries as $query) {
                                            foreach ($byColumns as $key => $selector) {
                                                if ($selector == 'id') {
                                                    $builder->{ $key == 0 ? 'where' : 'orWhere' }($relation[0].'.'.$selector, $query);
                                                } else {
                                                    $builder->{ $key == 0 ? 'where' : 'orWhere' }($relation[0].'.'.$selector, 'like', '%'.$query.'%');
                                                }
                                            }
                                        }
                                    });
                                } elseif ($this->model->hasFieldParam($column, 'belongsToMany')) {
                                    $relation = explode(',', $this->model->getField($column)['belongsToMany']);

                                    $byColumns = $this->getNamesBuilder($relation, $columns);

                                    //We does not have columns for filter
                                    if ( count($byColumns) == 0 ){
                                        return;
                                    }

                                    $builder->orWhereHas(trim_end($column, '_id'), function ($builder) use ($byColumns, $relation, $queries) {
                                        foreach ($queries as $query) {
                                            foreach ($byColumns as $key => $selector) {
                                                if ($selector == 'id') {
                                                    $builder->{ $key == 0 ? 'where' : 'orWhere' }($relation[0].'.'.$selector, $query);
                                                } else {
                                                    $builder->{ $key == 0 ? 'where' : 'orWhere' }($relation[0].'.'.$selector, 'like', '%'.$query.'%');
                                                }
                                            }
                                        }
                                    });
                                }

                                //Find by fulltext in query string
                                elseif ($this->model->hasFieldParam($column, 'locale')) {
                                    //Search for all inserted words
                                    foreach ($queries as $key => $query) {
                                        $builder->where(DB::raw('CONVERT(LOWER('.$tableColumn.') USING utf8)'), 'like', '%'.mb_strtolower($query).'%');
                                    }
                                } else {
                                    //Search for all inserted words
                                    foreach ($queries as $key => $query) {
                                        $builder->where($tableColumn, 'like', '%'.$query.'%');
                                    }
                                }
                            });
                        }
                    });
                }
            });
        }


        return $query;
    }

    /*
     * Get all columns from foreign relationships
     */
    private function getNamesBuilder($relation, $columns = [])
    {
        if (array_key_exists(1, $relation) && count($columns) > 1) {
            $model = Admin::getModelByTable($relation[0]);

            $relationColumns = $this->model->getRelationshipNameBuilder($relation[1]);

            return array_values(array_filter($relationColumns, function($column) use ($model) {
                if ( in_array($column, ['id', $model->getKeyName()]) ) {
                    return true;
                }

                return $model->getField($column) ? true : false;
            }));
        } else {
            return ['id'];
        }
    }

    /*
     * Apply pagination for given eloqment builder
     */
    protected function paginateRecords($query, $initialRequest)
    {
        //If limit is not enabled
        if ($this->limit <= 0) {
            return;
        }

        //If is first loading of first page and model is in reversed mode, then return last x rows.
        if ($this->page == 1 && $initialRequest && $this->model->isReversed() === true) {
            $count = $query->count();
            $take = $this->limit - ((ceil($count / $this->limit) * $this->limit) - $count);

            $query->offset($count - $take)->take($take);

            return;
        }

        $start = $this->limit * $this->page;
        $offset = $start - $this->limit;

        $query->offset($offset)->take($this->limit);
    }

    /*
     * Returns which model dependencies have to be loaded
     */
    private function getDependeciesIntoQuery()
    {
        $with = [];

        //Load relationships
        foreach ($this->model->getFields() as $key => $field) {
            if ($this->model->hasFieldParam($key, 'belongsTo')) {
                $with[] = substr($key, 0, -3);
            }

            if ($this->model->hasFieldParam($key, 'belongsToMany')) {
                $with[] = $key;
            }
        }

        return $with;
    }

    /*
     * Returns filtered and paginated rows from administration
     */
    private function getRowsDataQuery($callback = null, $withDependencies = false)
    {
        $query = $this->model->newQuery();

        if ( $withDependencies === true ) {
            //Get model dependencies
            $with = $this->getDependeciesIntoQuery();

            //Get base columns from database with relationships
            $query = $query->with($with);
        }

        //Filter by localization
        if ($this->languageId > 0) {
            $query->localization($this->languageId);
        }

        //Filter rows by language id and parent id
        $query->filterByParent($this->parentId, $this->parentTable);

        //Filter by scopes
        $query->filterByScopes($this->parentId, $this->scopes);

        //Filter by fields Group::where clausule
        $query->filterByParentGroup();

        if (is_callable($callback)) {
            call_user_func_array($callback, [$query]);
        }

        return $query;
    }

    /*
     * Returns all rows with base fields
     */
    protected function getBaseRows($rows_data)
    {
        $rows = [];

        foreach ($rows_data as $row) {
            //Return just base fields
            $row->justBaseFields(true);

            $rows[] = $row->getMutatedAdminAttributes(true);
        }

        return $rows;
    }

    /*
     * Generate button
     */
    protected function generateButton($row)
    {
        if ($buttons = $this->model->getAdminButtons()) {
            $data = [];

            foreach ($buttons as $key => $buttonClass) {
                $button = new $buttonClass($row);

                if ($button->active === true) {
                    $data[$key] = [
                        'key' => self::getButtonKey($buttonClass),
                        'name' => $button->name,
                        'class' => $button->class,
                        'icon' => $button->icon,
                        'type' => $button->type,
                        'reloadAll' => $button->reloadAll,
                        'tooltipEncode' => $button->tooltipEncode,
                        'action' => $button->getAction(),
                    ];
                }
            }

            return $data;
        }

        return false;
    }

    public static function getButtonKey($buttonClass)
    {
        return class_basename($buttonClass);
    }

    /*
     * Generate buttons properties for each row
     */
    public function generateButtonsProperties($rows)
    {
        $buttons = [];

        foreach ($rows as $row) {
            if ($button = $this->generateButton($row)) {
                $buttons[$row->getKey()] = $button;
            }
        }

        return $buttons;
    }

    public function returnModelData($onlyIds = [], $initialRequest)
    {
        try {
            $withoutRows = $this->returnNoData($onlyIds);

            if (! $withoutRows) {
                $paginatedRowsData = $this->getRowsDataQuery(function ($query) use ($onlyIds, $initialRequest) {
                    //Get specific id
                    if ($onlyIds && count($onlyIds) > 0) {
                        $query->whereIn($this->model->fixAmbiguousColumn($this->model->getKeyName()), $onlyIds);
                    } else {
                        $this->paginateRecords($query, $initialRequest);
                    }

                    //Search in rows
                    $this->checkForSearching($query);
                }, true)->get();

                $totalResultsCount = $this->getRowsDataQuery();
            }

            $data = [
                'rows' => $withoutRows ? [] : $this->getBaseRows($paginatedRowsData),
                'count' => $withoutRows ? 0 : $this->checkForSearching($totalResultsCount)->count(),
                'limit' => $this->limit,
                'page' => $this->page,
                'buttons' => $withoutRows ? [] : $this->generateButtonsProperties($paginatedRowsData),
            ];
        } catch (\Illuminate\Database\QueryException $e) {
            autoAjax()->mysqlError($e)->throw();
        }

        return $data;
    }

    private function returnNoData(array $onlyIds)
    {
        if ( admin()->hasAccess($this->model, 'read') === false ){
            return true;
        }

        if ( $this->limit === 0 ){
            return true;
        }

        //We want retrieve only specific rows, we can allow get this rows.
        if ( count($onlyIds) > 0 ){
            return false;
        }

        //We cant return data when listing in non existing parent
        if ( $this->parentTable && !$this->parentId ){
            return true;
        }

        return false;
    }
}
