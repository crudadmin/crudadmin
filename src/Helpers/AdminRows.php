<?php

namespace Gogol\Admin\Helpers;

use Ajax;
use Gogol\Admin\Models\Model as AdminModel;

class AdminRows
{
    protected $model = null;

    public function __construct(AdminModel $model)
    {
        $this->model = $model;
    }

    private function isPrimaryKey($column, $columns)
    {
        if ( in_array($column, ['id']) )
            return true;

        //If is correct relationship id
        if ( count($columns) == 1 && $this->model->hasFieldParam($column, 'belongsTo') )
            return true;

        //If is select, but not multiple
        if ( $this->isSelectColumn($column) )
            return true;

        return false;
    }

    private function isSelectColumn($column)
    {
        return $column && $this->model->isFieldType($column, ['select', 'radio']) && ! $this->model->hasFieldParam($column, 'multiple');
    }

    /*
     * Apply multi-text search scope for given query
     */
    protected function checkForSearching($query)
    {
        if ( request()->has('query') )
        {
            $search = trim(preg_replace("/(\s+)/", ' ', str_replace('%', '', request('query'))));

            //If is more than 3 chars for searching
            if ( strlen($search) >= 3 || ($this->isSelectColumn(request('column')) || is_numeric($search)) )
            {
                $columns = array_merge(array_keys($this->model->getFields()), [ 'id' ]);
                $queries = explode(' ', $search);

                //If is valid column
                if ( in_array(request('column'), $columns) )
                {
                    $columns = [ request('column') ];
                }

                //Remove multi relationship column
                foreach ($columns as $key => $column)
                {
                    if ( $this->model->hasFieldParam($column, 'belongsToMany') )
                        unset($columns[$key]);
                }

                //Search scope
                $query->where(function($builder) use ( $columns, $queries ) {
                    foreach ($columns as $key => $column)
                    {
                        //Search in all columns
                        $builder->{ $key == 0 ? 'where' : 'orWhere' }(function($builder) use ( $columns, $column, $queries ) {

                            //Find exact id, value
                            if ( $this->isPrimaryKey($column, $columns) ){
                                foreach ($queries as $query)
                                    $builder->where($column, $query);
                            }

                            //Find by data in relation
                            if ( $this->model->hasFieldParam($column, 'belongsTo') ) {
                                $relation = explode(',', $this->model->getField($column)['belongsTo']);

                                $builder->orWhereHas(rtrim($column, '_id'), function($builder) use( $relation, $queries ) {
                                    foreach ($queries as $query){
                                        foreach ($this->model->getRelationshipNameBuilder($relation[1]) as $key => $selector) {
                                            if ( $selector == 'id' )
                                                $builder->{ $key == 0 ? 'where' : 'orWhere' }($selector, $query);
                                            else
                                                $builder->{ $key == 0 ? 'where' : 'orWhere' }($selector, 'like', '%'.$query.'%');
                                        }
                                    }
                                });
                            }

                            //Find by fulltext in query string
                            else {
                                //Search for all inserted words
                                foreach ($queries as $query)
                                    $builder->where($column, 'like', '%'.$query.'%');
                            }

                        });
                    }
                });
            }
        }

        return $query;
    }

    /*
     * Apply pagination for given eloqment builder
     */
    protected function paginateRecords($query, $limit, $page, $count = null)
    {
        if ( $limit == 0 )
            return;

        //If is first loading of first page and model is in reversed mode, then return last x rows.
        if ( $page == 1 && $count !== null && $count == 0 && $this->model->isReversed() === true )
        {
            $count = $query->count();
            $take = $limit - ((ceil($count / $limit) * $limit) - $count);

            $query->offset( $count - $take )->take($take);
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
        foreach ($this->model->getFields() as $key => $field)
        {
            if ( $this->model->hasFieldParam($key, 'belongsTo') )
            {
                $with[] = substr($key, 0, -3);
            }

            if ( $this->model->hasFieldParam($key, 'belongsToMany') )
            {
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
        $query = $this->model->getAdminRows()->with($with);

        //Filter rows by language id and parent id
        $query->filterByParentOrLanguage($subid, $langid, $parent_table);

        if ( is_callable( $callback ) )
            call_user_func_array($callback, [$query]);

        return $query->get();
    }

    /*
     * Returns all rows with base fields
     */
    protected function getBaseRows($rows_data)
    {
        $rows = [];

        foreach ($rows_data as $row)
        {
            //Return just base fields
            $row->justBaseFields(true);

            $rows[] = $row->getAdminAttributes();
        };

        return $rows;
    }

    /*
     * Generate button
     */
    protected function generateButton($row)
    {
        if ( $buttons = $this->model->getProperty('buttons') )
        {
            $data = [];

            foreach ($buttons as $key => $button_class)
            {
                $button = new $button_class($row);

                if ( $button->active === true )
                {
                    $data[] = [
                        'name' => $button->name,
                        'class' => $button->class,
                        'icon' => $button->icon,
                        'reloadAll' => $button->reloadAll,
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

        foreach ($rows as $row)
        {
            if ( $button = $this->generateButton( $row ) )
                $buttons[ $row->getKey() ] = $button;
        }

        return $buttons;
    }

    /*
     * Return rendered blade layouts
     */
    protected function getLayouts($count)
    {
        $layouts = [];

        if ( $count > 0 )
            return [];

        foreach ((array)$this->model->getProperty('layouts') as $class)
        {
            $layout = new $class;

            if ( ($view = $layout->build()) instanceof \Illuminate\View\View )
            {
                $layouts[] = [
                    'position' => $layout->position,
                    'view' => $view->render(),
                ];
            }
        }

        return $layouts;
    }

    public function returnModelData($parent_table, $subid, $langid, $limit, $page, $count = null, $id = false)
    {
        try {
            $without_parent = $parent_table && (int)$subid == 0;

            if ( ! $without_parent )
            {
                $paginated_rows_data = $this->getRowsData($subid, $langid, function($query) use ( $limit, $page, $count, $id ) {

                    //Get specific id
                    if ( $id !== false )
                        $query->where('id', $id);

                    //Search in rows
                    $this->checkForSearching($query, $this->model);

                    //Paginate rows
                    $this->paginateRecords($query, $limit, $page, $count);
                }, $parent_table );

                $all_rows_data = $this->model->getAdminRows()->filterByParentOrLanguage($subid, $langid, $parent_table);
            }


            $data = [
                'rows' => $without_parent ? [] : $this->getBaseRows( $paginated_rows_data ),
                'count' => $without_parent ? 0 : $this->checkForSearching( $all_rows_data )->count(),
                'page' => $page,
                'buttons' => $without_parent ? [] : $this->generateButtonsProperties($paginated_rows_data),
                'layouts' => $this->getLayouts($count),
            ];
        } catch (\Illuminate\Database\QueryException $e) {
            return Ajax::mysqlError($e);
        }

        return $data;
    }
}

?>