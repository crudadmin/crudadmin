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
            'user' => auth()->user()->withAvatarPath(),
            'models' => $this->getAppTree(),
            'languages' => $this->getLanguages(),
            'requests' => [
                'show' => action('\Gogol\Admin\Controllers\DataController@show', [':model', ':id']),
                'store' => action('\Gogol\Admin\Controllers\DataController@store'),
                'update' => action('\Gogol\Admin\Controllers\DataController@update'),
                'delete' => action('\Gogol\Admin\Controllers\DataController@delete'),
                'togglePublishedAt' => action('\Gogol\Admin\Controllers\DataController@togglePublishedAt'),
                'updateOrder' => action('\Gogol\Admin\Controllers\DataController@updateOrder', [':model', ':id', ':subid']),
                'download' => action('\Gogol\Admin\Controllers\DownloadController@index'),
                'rows' => action('\Gogol\Admin\Controllers\LayoutController@getRows', [':model', ':subid', ':langid', ':limit', ':page', ':count']),
            ],
        ];

    }

    public function returnModelData($model, $subid, $langid, $limit, $page)
    {
        return [
            'rows' => $model->getBaseRows($subid, $langid, function($query) use ( $limit, $page ) {
                if ( $limit == 0 )
                    return;

                $start = $limit * $page;
                $offset = $start - $limit;

                $query->offset($offset)->take($limit);
            }),
            'count' => $model->filterByParentOrLanguage($subid, $langid)->count(),
            'page' => $page,
        ];
    }

    /*
     * Returns paginated rows and all required model informations
     */
    public function getRows($table, $subid, $langid, $limit, $page, $count)
    {
        $model = Admin::getModelByTable($table);

        //Check if user has allowed model
        if ( !$model || ! auth()->user()->hasAccess( $model ) )
            Ajax::permissionsError();

        if ( $count == 0 )
            $model->withAllOptions(true);

        return $this->makePage( $model, $this->returnModelData( $model, $subid, $langid, $limit, $page ), false);
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
            if ( ! auth()->user()->hasAccess( $model ) )
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
            if ( ! auth()->user()->hasAccess( $child_model ) )
                continue;

            //If is deactivated model
            if ( $child_model->getProperty('active') === false )
                continue;

            $childs[ $child_model->getTable() ] = $this->makePage($child_model);
        }

        return array_merge((array)$data, [
            'name' => $model->getProperty('name'),
            'settings' => (array)$model->getProperty('settings'),
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

    protected function getLastId($model)
    {
        if ( ($row = $model->select('id')->orderBy('id', 'DESC')->first()) === null )
            return 0;

        return $row->getKey();
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