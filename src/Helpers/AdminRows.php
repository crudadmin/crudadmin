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

    /*
     * Apply multi-text search scope for given query
     */
    protected function checkForSearching($query)
    {
        if ( request()->has('query') )
        {
            $search = trim(preg_replace("/(\s+)/", ' ', str_replace('%', '', request('query'))));

            //If is more than 3 chars for searching
            if ( strlen($search) >= 3 || is_numeric($search) )
            {
                $columns = array_merge(array_keys($this->model->getFields()), [ 'id' ]);
                $queries = explode(' ', $search);

                //If is valid column
                if ( in_array(request('column'), $columns) )
                {
                    $columns = [ request('column') ];
                }

                //Remove fake column
                foreach ($columns as $key => $column)
                {
                    if ( $this->model->hasFieldParam($column, 'belongsToMany') )
                        unset($columns[$key]);
                }

                //Search scope
                $query->where(function($builder) use ( $columns, $queries ) {
                    foreach ($columns as $column)
                    {
                        //Search in all columns
                        $builder->orWhere(function($builder) use ( $column, $queries ) {

                            //Search for all inserted words
                            foreach ($queries as $query)
                            {
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
    protected function generateButtonsProperties($rows)
    {
        $buttons = [];

        foreach ($rows as $row)
        {
            if ( $button = $this->generateButton( $row ) )
                $buttons[ $row->getKey() ] = $button;
        }

        return $buttons;
    }

    public function returnModelData($parent_table, $subid, $langid, $limit, $page, $count = null)
    {
        try {
            $paginated_rows_data = $this->getRowsData($subid, $langid, function($query) use ( $limit, $page, $count ) {
                //Search in rows
                $this->checkForSearching($query, $this->model);

                //Paginate rows
                $this->paginateRecords($query, $limit, $page, $count);
            }, $parent_table );

            $all_rows_data = $this->model->getAdminRows()->filterByParentOrLanguage($subid, $langid, $parent_table);

            $data = [
                'rows' => $this->getBaseRows( $paginated_rows_data ),
                'count' => $this->checkForSearching( $all_rows_data )->count(),
                'page' => $page,
                'buttons' => $this->generateButtonsProperties($paginated_rows_data),
            ];
        } catch (\Illuminate\Database\QueryException $e) {
            return Ajax::mysqlError($e);
        }

        return $data;
    }
}

?>