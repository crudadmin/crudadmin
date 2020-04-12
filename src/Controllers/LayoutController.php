<?php

namespace Admin\Controllers;

use Ajax;
use Admin;
use Localization;
use Admin\Fields\Group;
use Admin\Helpers\AdminRows;
use Illuminate\Http\Request;
use Admin\Controllers\Controller as BaseController;

class LayoutController extends BaseController
{
    public function index()
    {
        return [
            'version' => Admin::getVersion(),
            'version_resources' => Admin::getResourcesVersion(),
            'version_assets' => Admin::getAssetsVersion(),
            'license_key' => config('admin.license_key'),
            'user' => admin()->getAdminUser(),
            'models' => $this->getAppTree(true),
            'languages' => $this->getLanguages(),
            'gettext' => config('admin.gettext', false),
            'locale' => app()->getLocale(),
            'localization' => trans('admin::admin'),
            'dashboard' => $this->getDashBoard(),
            'requests' => [
                'show' => action('\Admin\Controllers\Crud\DataController@show', [':model', ':id', ':subid']),
                'store' => action('\Admin\Controllers\Crud\InsertController@store'),
                'update' => action('\Admin\Controllers\Crud\UpdateController@update'),
                'delete' => action('\Admin\Controllers\Crud\DataController@delete'),
                'togglePublishedAt' => action('\Admin\Controllers\Crud\DataController@togglePublishedAt'),
                'getHistory' => action('\Admin\Controllers\Crud\DataController@getHistory', [':model', ':id']),
                'updateOrder' => action('\Admin\Controllers\Crud\DataController@updateOrder'),
                'buttonAction' => action('\Admin\Controllers\Crud\DataController@buttonAction'),
                'download' => action('\Admin\Controllers\DownloadController@index'),
                'rows' => action('\Admin\Controllers\LayoutController@getRows', [':model', ':parent', ':subid', ':langid', ':limit', ':page', ':count']),
                'translations' => action('\Admin\Controllers\GettextController@getTranslations', [':id', ':table']),
                'update_translations' => action('\Admin\Controllers\GettextController@updateTranslations', [':id', ':table']),
            ],
        ];
    }

    /*
     * Return dashboard content
     */
    private function getDashBoard()
    {
        $path = config('admin.dashboard', resource_path('views/admin/dashboard.blade.php'));

        if (! file_exists($path)) {
            return '';
        }

        return view()->file($path)->render();
    }

    /**
     * Set parent table into actual selected child model in admin
     *
     * @param  string  &$parentTable
     * @param  int  $subid
     */
    private function setParentModelIntoEloquent($model, &$parentTable, $subid)
    {
        if ($parentTable == '0') {
            $parentTable = null;
        } else {
            //Set parent row into model
            $parentRow = Admin::getModelByTable($parentTable)
                                ->withoutGlobalScopes()
                                ->find($subid);

            $model->setParentRow($parentRow);
        }
    }

    /*
     * Returns paginated rows and all required model informations
     */
    public function getRows($table, $parentTable, $subid, $langid, $limit, $page, $count)
    {
        $model = Admin::getModelByTable($table);

        $initialOpeningRequest = $count == 0;

        //Check if user has allowed model
        if (! $model || ! admin()->hasAccess($model)) {
            Ajax::permissionsError();
        }

        //Set parent row
        $this->setParentModelIntoEloquent($model, $parentTable, $subid);

        $data = [];

        //Model tree need to be generated at first order
        //Because we want refresh all fields property by booted session.
        $modelTree = $this->makePage(
            $model,
            false,
            false,
            $initialOpeningRequest
        );

        //On initial admin request
        if ( $initialOpeningRequest === true && method_exists($model, 'beforeInitialAdminRequest') ) {
            $data['model'] = $model->beforeInitialAdminRequest();
        }

        //Add token
        $data['token'] = csrf_token();

        //Add model data
        $data['model'] = array_merge(@$data['model'] ?: [], $modelTree);

        //Add rows data
        $data = array_merge(
            $data,
            (new AdminRows($model))->returnModelData($parentTable, $subid, $langid, $limit, $page, $count)
        );

        //Modify intiial request data
        if ( $initialOpeningRequest === true && method_exists($model, 'afterInitialAdminRequest') ) {
            $data['model'] = $model->afterInitialAdminRequest($data['model']);
        }

        return $data;
    }

    /*
     * Return fields with correct order of options in select for administration
     * because browser does not know correct values of keys in object
     *
     * Every row in options will be represented as array of key and value,
     */
    private function updateOptionsForm($key, $field, &$fields)
    {
        if (! array_key_exists('options', $field)) {
            return;
        }

        $data = [];

        foreach ($field['options'] as $k => $v) {
            $data[] = [$k, $v];
        }

        $fields[$key]['options'] = $data;
    }

    private function findEqualOptions($key, $field, &$fields)
    {
        if (! array_key_exists('options', $field) || count($options = $field['options']) == 0) {
            return;
        }

        foreach ($fields as $k => $f) {
            if ($key == $k || ! array_key_exists('options', $f)) {
                continue;
            }

            //If this set of options exists in other field already
            if ($options == $f['options']) {
                $fields[$key]['options'] = '$.'.$k;
            }
        }
    }

    protected function getModelFields($model, $withOptions = false)
    {
        //If is first request into table, then load also all options from fields
        if ($withOptions === true) {
            $model->withAllOptions();
        }

        $fields = $model->getFields();

        foreach ($fields as $key => $field) {
            $this->updateOptionsForm($key, $field, $fields);

            $this->findEqualOptions($key, $field, $fields);
        }

        return $fields;
    }

    private function skipModelInTree($model)
    {
        //Get basename relations
        $belongsToModel = $model->getBelongsToRelation(true);

        $count = count($belongsToModel);

        //If is model related recursive to itself
        if (
            ($count == 1 && in_array(class_basename(get_class($model)), $belongsToModel))
            || $model->getProperty('inMenu') === true
        ) {
            return false;
        }

        //If model is some child, or
        if ($count > 0) {
            return true;
        }

        return false;
    }

    private function groupPrefix($key)
    {
        return '#$_'.$key;
    }

    /**
     * Returns full app tree.
     * @return [array]
     */
    public function getAppTree($initial_request = false)
    {
        $models = Admin::getAdminModels();

        $pages = [];

        $groups = [];

        //Bind pages into groups
        foreach ($models as $model) {
            if ($this->skipModelInTree($model)) {
                continue;
            }

            //Check if user has allowed model
            if (! admin()->hasAccess($model, 'read') && ! admin()->hasAccess($model, 'insert')) {
                $model->setProperty('disableModel', true);
            }

            $page = $this->makePage($model, true, $initial_request);

            if ($model->hasModelGroup()) {
                $tree = $model->getModelGroupsTree();

                $count = count($tree);

                $reference = &$groups;

                foreach ($tree as $i => $group) {
                    $group_key = $group['key'];

                    //If groups does not exist into x-dimensional array
                    if (! array_key_exists($this->groupPrefix($group_key), $reference)) {
                        $reference[$this->groupPrefix($group_key)] = [
                            'name' => $group['name'],
                            'icon' => $group['icon'],
                            'submenu' => [],
                        ];
                    }

                    $reference = &$reference[$this->groupPrefix($group_key)]['submenu'];

                    //If is last group in array, then push model into group list
                    if ($i + 1 >= $count) {
                        $reference[$model->getTable()] = $page;
                    }
                }

                unset($reference);
            } else {
                $groups[$model->getTable()] = $page;
            }
        }

        return $this->addSlugPath($groups);
    }

    /**
     * Return build model JSON instance.
     * @param  object  $model          model instance
     * @param  bool $withChilds     return all model childs
     * @param  bool $layout_request if is first request for admin boot
     * @return json
     */
    protected function makePage($model, $withChilds = true, $initial_request = false, $withOptions = false)
    {
        //We need refresh fields for actual fields rules. Modified eg. by session.
        //(some admin rules may not have available all fields. Or some properties may be changed)
        $model->getFields(null, true);

        $childs_models = $model->getModelChilds();
        $childs = [];

        foreach ($childs_models as $child_model) {
            if ($withChilds === false) {
                continue;
            }

            // Check if user has allowed model
            if (! admin()->hasAccess($child_model)) {
                $child_model->setProperty('disableModel', true);
            }

            $child = $child_model === $model ? '$_itself' : $this->makePage($child_model);

            $childs[$child_model->getTable()] = $child;
        }

        $data = [
            'name' => $model->getProperty('name'),
            'icon' => $model->getProperty('icon'),
            'settings' => $model->getModelSettings(),
            'active' => $model->getProperty('disableModel') ? false : $model->getProperty('active'),
            'foreign_column' => $model->getForeignColumn(),
            'without_parent' => $model->getProperty('withoutParent') ?: false,
            'global_relation' => $model->getProperty('globalRelation') ?: false,
            'in_tab' => $model->getProperty('inTab') ?: false,
            'in_menu' => $model->getProperty('inMenu', false),
            'hidden_tabs' => $model->getProperty('hidden_tabs') ?: [],
            'reserved' => $model->getProperty('reserved') ?: false,
            'title' => $model->getProperty('title'),
            'columns' => $model->getBaseFields(),
            'inParent' => $model->getProperty('inParent') ?: false,
            'minimum' => $model->getProperty('minimum'),
            'maximum' => $model->getProperty('maximum'),
            'insertable' => $model->getProperty('insertable'),
            'editable' => $model->getProperty('editable'),
            'deletable' => $model->getProperty('deletable'),
            'publishable' => $model->getProperty('publishable'),
            'sortable' => $model->isSortable(),
            'orderBy' => $model->getProperty('orderBy'),
            'history' => $model->getProperty('history'),
            'fields' => $this->getModelFields($model, $withOptions),
            'fields_groups' => $model->getFieldsGroups(),
            'childs' => $childs,
            'localization' => $model->isEnabledLanguageForeign(),
            'components' => $model->getFieldsComponents($initial_request),
            'permissions' => $this->checkPermissions($model),
            'submenu' => [],
        ];

        $model->runAdminModules(function($module) use (&$data) {
            if ( method_exists($module, 'adminModelRender') ) {
                $module->adminModelRender($data);
            }
        });

        //Mutate all parameters
        if ( method_exists($model, 'adminModelRender') ) {
            $data = $model->adminModelRender($data);
        }

        return $data;
    }

    protected function checkPermissions($model)
    {
        $permissions = [];

        foreach ($model->getModelPermissions() as $permissionKey => $name) {
            $permissions[$permissionKey] = admin()->hasAccess($model, $permissionKey);
        }

        return $permissions;
    }

    /*
     * Add slug parameter into model page
     */
    protected function addSlugPath($pages)
    {
        $data = [];

        foreach ($pages as $key => $page) {
            $data[$key] = $page;

            if (! is_array($page)) {
                continue;
            }

            $data[$key]['slug'] = $key;
            $data[$key]['table'] = $key;

            foreach (['submenu', 'childs'] as $subkey) {
                if (array_key_exists($subkey, $page) && count($page[$subkey]) > 0) {
                    $data[$key][$subkey] = $this->addSlugPath($page[$subkey]);
                }
            }
        }

        return $data;
    }

    /*
     * Returns all languages
     */
    protected function getLanguages()
    {
        if (! Admin::isEnabledLocalization()) {
            return [];
        }

        return Localization::getLanguages();
    }
}
