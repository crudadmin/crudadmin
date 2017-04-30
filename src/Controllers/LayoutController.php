<?php

namespace Gogol\Admin\Controllers;

use Illuminate\Http\Request;

use Ajax;
use Admin;
use Localization;
use App\Http\Requests;
use App\Http\Controllers\Controller as BaseController;
use DB;

class LayoutController extends BaseController
{

    public function index()
    {
        return [
            'version' => Admin::getVersion(),
            'license_key' => config('admin.license_key'),
            'user' => auth()->guard('web')->user()->getAdminUser(),
            'models' => $this->getAppTree(),
            'languages' => $this->getLanguages(),
            'requests' => [
                'show' => action('\Gogol\Admin\Controllers\DataController@show', [':model', ':id']),
                'store' => action('\Gogol\Admin\Controllers\DataController@store'),
                'update' => action('\Gogol\Admin\Controllers\DataController@update'),
                'delete' => action('\Gogol\Admin\Controllers\DataController@delete'),
                'togglePublishedAt' => action('\Gogol\Admin\Controllers\DataController@togglePublishedAt'),
                'updateOrder' => action('\Gogol\Admin\Controllers\DataController@updateOrder'),
                'download' => action('\Gogol\Admin\Controllers\DownloadController@index'),
                'rows' => action('\Gogol\Admin\Controllers\LayoutController@getRows', [':model', ':parent', ':subid', ':langid', ':limit', ':page', ':count']),
            ],
        ];

    }

    /*
     * Apply multi-text search scope for given query
     */
    protected function checkForSearching($query, $model)
    {
        if ( request()->has('query') )
        {
            $search = trim(preg_replace("/(\s+)/", ' ', str_replace('%', '', request('query'))));

            //If is more than 3 chars for searching
            if ( strlen($search) >= 3 || is_numeric($search) )
            {
                $columns = array_merge(array_keys($model->getFields()), [ 'id' ]);
                $queries = explode(' ', $search);

                //If is valid column
                if ( in_array(request('column'), $columns) )
                {
                    $columns = [ request('column') ];
                }

                //Remove fake column
                foreach ($columns as $key => $column)
                {
                    if ( $model->hasFieldParam($column, 'belongsToMany') )
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
    protected function paginateRecords($query, $limit, $page)
    {
        if ( $limit == 0 )
            return;

        $start = $limit * $page;
        $offset = $start - $limit;

        $query->offset($offset)->take($limit);
    }

    public function returnModelData($model, $parent_table, $subid, $langid, $limit, $page)
    {
        try {
            $data = [
                'rows' => $model->getBaseRows($subid, $langid, function($query) use ( $limit, $page, $model ) {
                    //Search in rows
                    $this->checkForSearching($query, $model);

                    //Paginate rows
                    $this->paginateRecords($query, $limit, $page);
                }, $parent_table),
                'count' => $this->checkForSearching(
                                $model->getAdminRows()->filterByParentOrLanguage($subid, $langid, $parent_table),
                                $model)
                            ->count(),
                'page' => $page,
            ];
        } catch (\Illuminate\Database\QueryException $e) {
            return Ajax::error('Nastala nečakaná chyba, pravdepodobne ste nespústili migráciu modelov pomocou príkazu:<br><strong>php artisan admin:migrate</strong>', null, null, 500);
        }

        return $data;
    }

    /*
     * Returns paginated rows and all required model informations
     */
    public function getRows($table, $parent_table, $subid, $langid, $limit, $page, $count)
    {
        $model = Admin::getModelByTable($table);

        //Check if user has allowed model
        if ( !$model || ! auth()->guard('web')->user()->hasAccess( $model ) )
            Ajax::permissionsError();

        if ( $count == 0 ){
            $model->withAllOptions(true);
        }

        if ( $parent_table == '0' )
            $parent_table = null;

        return $this->makePage( $model, $this->returnModelData( $model, $parent_table, $subid, $langid, $limit, $page ), false);
    }

    /**
     * Returns full app tree
     * @return [array]
     */
    public function getAppTree()
    {
        $models = Admin::getAdminModels();

        $pages = [];

        $groups = [];

        //Bind pages into groups
        foreach ($models as $model)
        {
            if ( $model->getProperty('belongsToModel') != null )
                continue;

            //Check if user has allowed model
            if ( ! auth()->guard('web')->user()->hasAccess( $model ) )
                continue;

            //If is deactivated model
            if ( $model->getProperty('active') === false )
                continue;

            $page = $this->makePage($model);

            $group_name = $model->hasGroup() ? $model->getGroup() : '_root';

            //Create and add rows into group
            if ( $model->hasGroup() ){
                $groups[ '$' . str_slug($group_name) ]['name'] = $group_name;
                $groups[ '$' . str_slug($group_name) ]['submenu'][ $model->getTable() ] = $page;
            } else {
                $groups[ $model->getTable() ] = $page;
            }
        }

        return $this->addSlugPath( $groups );
    }

    protected function makePage($model, $data = null, $withChilds = true)
    {
        $fields = $model->getFields();

        $childs_models = $model->getChilds();

        $childs = [];

        foreach ($childs_models as $child_model)
        {
            if ( $withChilds === false )
                continue;

            //Check if user has allowed model
            if ( ! auth()->guard('web')->user()->hasAccess( $child_model ) )
                continue;

            //If is deactivated model
            if ( $child_model->getProperty('active') === false )
                continue;

            $childs[ $child_model->getTable() ] = $this->makePage($child_model);
        }

        return array_merge((array)$data, [
            'name' => $model->getProperty('name'),
            'settings' => $model->getModelSettings(),
            'foreign_column' => $model->getForeignColumn(),
            'title' => $model->getProperty('title'),
            'columns' => $model->getBaseFields(),
            'minimum' => $model->getProperty('minimum'),
            'maximum' => $model->getProperty('maximum'),
            'insertable' => $model->getProperty('insertable'),
            'editable' => $model->getProperty('editable'),
            'deletable' => $model->getProperty('deletable'),
            'publishable' => $model->getProperty('publishable'),
            'sortable' => $model->getProperty('sortable'),
            'fields' => $fields,
            'childs' => $childs,
            'localization' => $model->isEnabledLanguageForeign(),
            'submenu' => [],
        ]);
    }

    /*
     * Add slug parameter into model page
     */
    protected function addSlugPath($pages)
    {
        $data = [];

        foreach ($pages as $key => $page)
        {
            $data[$key] = $page;
            $data[$key]['slug'] = $key;

            foreach (['submenu', 'childs'] as $subkey)
            {
                if ( array_key_exists($subkey, $page) && count($page[$subkey]) > 0 )
                    $data[$key][$subkey] = $this->addSlugPath($page[$subkey]);
            }
        }

        return $data;
    }

    /*
     * Returns all languages
     */
    protected function getLanguages()
    {
        if ( ! Admin::isEnabledMultiLanguages() )
            return [];

        return Localization::getLanguages();
    }
}