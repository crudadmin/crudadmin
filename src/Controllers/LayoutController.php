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
            'version_assets' => Admin::getAssetsVersion(),
            'license_key' => config('admin.license_key'),
            'user' => auth()->guard('web')->user()->getAdminUser(),
            'models' => $this->getAppTree(true),
            'languages' => $this->getLanguages(),
            'gettext' => config('admin.gettext', false),
            'locale' => config('admin.locale', app()->getLocale()),
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
                'translations' => action('\Admin\Controllers\GettextController@getTranslations', [':id']),
                'update_translations' => action('\Admin\Controllers\GettextController@updateTranslations', [':id']),
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

    /*
     * Returns paginated rows and all required model informations
     */
    public function getRows($table, $parent_table, $subid, $langid, $limit, $page, $count)
    {
        $model = Admin::getModelByTable($table);

        //Check if user has allowed model
        if (! $model || ! auth()->guard('web')->user()->hasAccess($model)) {
            Ajax::permissionsError();
        }

        if ($parent_table == '0') {
            $parent_table = null;
        } else {
            //Set parent row into model
            $parent_row = Admin::getModelByTable($parent_table)->withoutGlobalScopes()->find($subid);

            $model->setParentRow($parent_row);
        }

        $data = (new AdminRows($model))->returnModelData($parent_table, $subid, $langid, $limit, $page, $count);

        //Add token
        $data['token'] = csrf_token();

        return $this->makePage(
            $model,
            $data,
            false,
            false,
            $count == 0
        );
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
            || $model->getProperty('inMenu', false) === true
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
            if (! auth()->guard('web')->user()->hasAccess($model)) {
                $model->setProperty('active', false);
            }

            $page = $this->makePage($model, null, true, $initial_request);

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
     * @param  object  $data           additional data for request
     * @param  bool $withChilds     return all model childs
     * @param  bool $layout_request if is first request for admin boot
     * @return json
     */
    protected function makePage($model, $data = null, $withChilds = true, $initial_request = false, $withOptions = false)
    {
        $childs_models = $model->getModelChilds();

        $childs = [];

        foreach ($childs_models as $child_model) {
            if ($withChilds === false) {
                continue;
            }

            // Check if user has allowed model
            if (! auth()->guard('web')->user()->hasAccess($child_model)) {
                $child_model->setProperty('active', false);
            }

            $child = $child_model === $model ? '$_itself' : $this->makePage($child_model);

            $childs[$child_model->getTable()] = $child;
        }

        return array_merge((array) $data, [
            'name' => $model->getProperty('name'),
            'icon' => $model->getModelIcon(),
            'settings' => $model->getModelSettings(),
            'active' => $model->getProperty('active'),
            'foreign_column' => $model->getForeignColumn(),
            'without_parent' => $model->getProperty('withoutParent') ?: false,
            'in_tab' => $model->getProperty('inTab') ?: false,
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
            'submenu' => [],
        ]);
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
