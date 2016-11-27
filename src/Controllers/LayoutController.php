<?php

namespace Gogol\Admin\Controllers;

use Illuminate\Http\Request;

use Admin;
use Localization;
use App\Http\Requests;
use App\Http\Controllers\Controller as BaseController;

class LayoutController extends BaseController
{

    public function index()
    {
        // sleep(1);
        return [
            'user' => $this->getUser(),
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
                'refresh' => action('\Gogol\Admin\Controllers\LayoutController@refresh', [':model', ':id']),
            ],
        ];

    }

    public function refresh($table, $from_id)
    {
        $models = Admin::getAdminModels();

        //Bind pages into groups
        foreach ($models as $model)
        {
            if ( $model->getTable() == $table )
            {
                return $this->makePage( $model, $from_id );
            }

        }

        return [];
    }

    public function getUser()
    {
        $user = auth()->user();

        if ( $user->avatar )
            $user->avatar = $user->avatar->thumbs->path;

        return $user;
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

            $page = $this->makePage($model, -1);

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

    protected function makePage($model, $from_id = 0)
    {
        $fields = $model->getFields();

        $childs_models = $model->getChilds();

        $childs = [];

        foreach ($childs_models as $child_model)
        {
            $childs[ $child_model->getTable() ] = $this->makePage($child_model);
        }

        return [
            'name' => $model->getProperty('name'),
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
            'rows' => $model->getBaseRows($from_id),
            'childs' => $childs,
            'active' => $model->getProperty('active'),
            'localization' => $model->isEnabledLanguageForeign(),
            'submenu' => [],
        ];
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