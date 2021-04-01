<?php

namespace Admin\Helpers;

use Ajax;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Admin\Eloquent\AdminModel;
use Admin;

class AdminRows
{
    protected $model = null;

    /**
     * Class constructor
     *
     * @param  Admin\Eloquent\AdminModel  $model
     */
    public function __construct(AdminModel $model)
    {
        $this->model = $model;
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
            $itemQuery = @$item['query'];
            $itemQueryTo = @$item['query_to'];
            $column = @$item['column'];

            if (!($itemQuery || $itemQueryTo)) {
                continue;
            }

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
                                    $builder->where(function ($builder) use ($column, $search, $search_to) {
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
                                else {
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

            return array_filter($relationColumns, function($column) use ($model) {
                if ( in_array($column, ['id', $model->getKeyName()]) ) {
                    return true;
                }

                return $model->getField($column) ? true : false;
            });
        } else {
            return ['id'];
        }
    }

    /*
     * Apply pagination for given eloqment builder
     */
    protected function paginateRecords($query, $limit, $page, $count = null)
    {
        if ($limit == 0) {
            return;
        }

        //If is first loading of first page and model is in reversed mode, then return last x rows.
        if ($page == 1 && $count !== null && $count == 0 && $this->model->isReversed() === true) {
            $count = $query->count();
            $take = $limit - ((ceil($count / $limit) * $limit) - $count);

            $query->offset($count - $take)->take($take);

            return;
        }

        $start = $limit * $page;
        $offset = $start - $limit;

        $query->offset($offset)->take($limit);
    }

    /*
     * Returns which model dependencies have to be loaded
     */
    protected function loadWithDependecies()
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
    protected function getRowsData($subid, $langid, $callback = null, $parent_table = null)
    {
        //Get model dependencies
        $with = $this->loadWithDependecies();

        //Get base columns from database with relationships
        $query = $this->model->adminRows()->with($with);

        //Filter rows by language id and parent id
        $query->filterByParentOrLanguage($subid, $langid, $parent_table);

        $query->filterByParentGroup();

        if (is_callable($callback)) {
            call_user_func_array($callback, [$query]);
        }

        return $query->get();
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

            $rows[] = $row->getMutatedAdminAttributes();
        }

        return $rows;
    }

    /*
     * Generate button
     */
    protected function generateButton($row)
    {
        if ($buttons = array_values(array_filter((array) $this->model->getProperty('buttons')))) {
            $data = [];

            foreach ($buttons as $key => $button_class) {
                $button = new $button_class($row);

                if ($button->active === true) {
                    $data[$key] = [
                        'key' => class_basename($button),
                        'name' => $button->name,
                        'class' => $button->class,
                        'icon' => $button->icon,
                        'type' => $button->type,
                        'reloadAll' => $button->reloadAll,
                        'tooltipEncode' => $button->tooltipEncode,
                        'ask' => method_exists($button, 'ask') || method_exists($button, 'question'),
                    ];
                }
            }

            return $data;
        }

        return false;
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

    /*
     *
     */
    private function isInlineTemplateKey($key)
    {
        $positions = (new Layout)->available_positions;

        return in_array($key, $positions, true);
    }

    /*
     * Return rendered blade layouts
     */
    protected function getLayouts($count)
    {
        $layouts = [];

        if ($count > 0) {
            return [];
        }

        $i = 0;
        foreach ((array) $this->model->getProperty('layouts') as $key => $class) {
            //Load inline template
            if ($this->isInlineTemplateKey($key)) {
                $classes = array_wrap($class);

                foreach ($classes as $componentName) {
                    $layouts[] = [
                        'name' => strtoupper($componentName[0]).Str::camel(substr($componentName, 1)).'_'.$i.'AnonymousLayout',
                        'type' => 'vuejs',
                        'position' => $key,
                        'view' => (new Layout)->renderVueJs($componentName),
                        'component_name' => $componentName,
                    ];
                }
            }

            //Load template with layout class
            elseif (class_exists($class)) {
                $layout = new $class;

                $view = $layout->build();

                if (is_string($view) || $view instanceof \Illuminate\View\View) {
                    $is_blade = method_exists($view, 'render');

                    $layouts[] = [
                        'name' => class_basename($class),
                        'type' => $is_blade ? 'blade' : 'vuejs',
                        'position' => $layout->position,
                        'view' => $is_blade ? $view->render() : $view,
                    ];
                }
            }

            $i++;
        }

        return $layouts;
    }

    public function returnModelData($parent_table, $subid, $langid, $limit, $page, $count = null, $id = false)
    {
        try {
            $withoutData = $parent_table && (int) $subid == 0 || !admin()->hasAccess($this->model, 'read');

            if (! $withoutData) {
                $paginated_rows_data = $this->getRowsData($subid, $langid, function ($query) use ($limit, $page, $count, $id) {

                    //Get specific id
                    if ($id != false) {
                        if (is_numeric($id)) {
                            $query->where($this->model->getKeyName(), $id);
                        } elseif (is_array($id)) {
                            $query->whereIn($this->model->getKeyName(), $id);
                        }
                    }

                    //Search in rows
                    $this->checkForSearching($query);

                    //Paginate rows
                    if ($id == false) {
                        $this->paginateRecords($query, $limit, $page, $count);
                    }
                }, $parent_table);

                $all_rows_data = $this->model->adminRows()
                                             ->filterByParentOrLanguage($subid, $langid, $parent_table)
                                             ->filterByParentGroup();

            }

            $data = [
                'rows' => $withoutData ? [] : $this->getBaseRows($paginated_rows_data),
                'count' => $withoutData ? 0 : $this->checkForSearching($all_rows_data)->count(),
                'page' => $page,
                'buttons' => $withoutData ? [] : $this->generateButtonsProperties($paginated_rows_data),
                'layouts' => $this->getLayouts($count),
            ];
        } catch (\Illuminate\Database\QueryException $e) {
            return Ajax::mysqlError($e);
        }

        return $data;
    }
}
