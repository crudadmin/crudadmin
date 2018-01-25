<?php

namespace Gogol\Admin\Controllers;

use Illuminate\Http\Request;

use Ajax;
use Admin;
use Localization;
use App\Http\Requests;
use App\Http\Controllers\Controller as BaseController;
use Gogol\Admin\Fields\Group;
use Gogol\Admin\Helpers\AdminRows;
use DB;

class LayoutController extends BaseController
{

    public function index()
    {
        return [
            'version' => Admin::getVersion(),
            'version_assets' => Admin::getAssetsVersion(),
            'license_key' => config('admin.license_key'),
            'user' => auth()->guard('web')->user()->getAdminUser(),
            'models' => $this->getAppTree(),
            'languages' => $this->getLanguages(),
            'localization' => trans('admin::admin'),
            'requests' => [
                'show' => action('\Gogol\Admin\Controllers\DataController@show', [':model', ':id', ':subid']),
                'store' => action('\Gogol\Admin\Controllers\DataController@store'),
                'update' => action('\Gogol\Admin\Controllers\DataController@update'),
                'delete' => action('\Gogol\Admin\Controllers\DataController@delete'),
                'togglePublishedAt' => action('\Gogol\Admin\Controllers\DataController@togglePublishedAt'),
                'getHistory' => action('\Gogol\Admin\Controllers\DataController@getHistory', [':model', ':id']),
                'updateOrder' => action('\Gogol\Admin\Controllers\DataController@updateOrder'),
                'buttonAction' => action('\Gogol\Admin\Controllers\DataController@buttonAction'),
                'download' => action('\Gogol\Admin\Controllers\DownloadController@index'),
                'rows' => action('\Gogol\Admin\Controllers\LayoutController@getRows', [':model', ':parent', ':subid', ':langid', ':limit', ':page', ':count']),
            ],
        ];

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

        //If is first request into table, then load allso all options from fields
        if ( $count == 0 ){
            $model->withAllOptions(true);
        }

        if ( $parent_table == '0' )
            $parent_table = null;

        $data = (new AdminRows($model))->returnModelData( $parent_table, $subid, $langid, $limit, $page, $count );

        //Add token
        $data['token'] = csrf_token();

        return $this->makePage(
            $model,
            $data,
            false
        );
    }

    /*
     * Return fields with correct order of options in select for administration
     * because browser dont know correct values of keys in object
     *
     * Every row in options will be represented as array of key and value,
     */
    protected function getModelFields($model)
    {
        $fields = $model->getFields();

        foreach ($fields as $key => $field)
        {
            if ( array_key_exists('options', $field) )
            {
                $data = [];

                foreach ($field['options'] as $k => $v)
                {
                    $data[] = [$k, $v];
                }

                $fields[$key]['options'] = $data;
            }
        }

        return $fields;
    }

    private function skipModelInTree($model)
    {
        if ( $model->getProperty('belongsToModel') != null )
            return true;

        //Check if user has allowed model
        if ( ! auth()->guard('web')->user()->hasAccess( $model ) )
            return true;

        return false;
    }

    private function groupPrefix($key)
    {
        return '#$_' . $key;
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
            if ( $this->skipModelInTree($model) )
                continue;

            $page = $this->makePage($model);

            if ( $model->hasModelGroup() )
            {
                $tree = $model->getModelGroupsTree();
                $count = count($tree);

                $reference =& $groups;

                foreach ($tree as $i => $group) {
                    $group_key = $group['key'];

                    //If groups does not exist into x-dimensional array
                    if ( ! array_key_exists($this->groupPrefix($group_key), $reference) )
                        $reference[ $this->groupPrefix($group_key) ] = [
                            'name' => $group['name'],
                            'icon' => $group['icon'],
                            'submenu' => [],
                        ];

                    $reference =& $reference[$this->groupPrefix($group_key)]['submenu'];

                    //If is last group in array, then push model into group list
                    if ( $i + 1 >= $count ){
                        $reference[ $model->getTable() ] = $page;
                    }
                }

                unset($reference);
            } else {
                $groups[ $model->getTable() ] = $page;
            }

        }

        return $this->addSlugPath( $groups );
    }

    protected function makePage($model, $data = null, $withChilds = true)
    {
        $childs_models = $model->getModelChilds();

        $childs = [];

        foreach ($childs_models as $child_model)
        {
            if ( $withChilds === false || $child_model === $model )
                continue;

            //Check if user has allowed model
            if ( ! auth()->guard('web')->user()->hasAccess( $child_model ) )
                continue;

            $childs[ $child_model->getTable() ] = $this->makePage($child_model);
        }

        return array_merge((array)$data, [
            'name' => $model->getProperty('name'),
            'icon' => $model->getModelIcon(),
            'settings' => $model->getModelSettings(),
            'active' => $model->getProperty('active'),
            'foreign_column' => $model->getForeignColumn(),
            'without_parent' => $model->getProperty('withoutParent') ?: false,
            'in_tab' => $model->getProperty('inTab') ?: false,
            'reserved' => $model->getProperty('reserved') ?: false,
            'title' => $model->getProperty('title'),
            'columns' => $model->getBaseFields(),
            'minimum' => $model->getProperty('minimum'),
            'maximum' => $model->getProperty('maximum'),
            'insertable' => $model->getProperty('insertable'),
            'editable' => $model->getProperty('editable'),
            'deletable' => $model->getProperty('deletable'),
            'publishable' => $model->getProperty('publishable'),
            'sortable' => $model->isSortable(),
            'orderBy' => $model->getProperty('orderBy'),
            'history' => $model->getProperty('history'),
            'fields' => $this->getModelFields( $model ),
            'fields_groups' => Group::build($model),
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