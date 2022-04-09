<?php

namespace Admin\Controllers;

use Admin;
use AdminLocalization;
use Admin\Controllers\Controller as BaseController;
use Admin\Fields\Group;
use Admin\Helpers\AdminRows;
use Admin\Helpers\Layout;
use Admin\Helpers\Localization\AdminResourcesSyncer;
use Admin\Helpers\SecureDownloader;
use Admin\Helpers\SheetDownloader;
use Ajax;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Localization;

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
            'admin_languages' => $this->getAdminLanguages(),
            'admin_language' => admin()->language ? admin()->language : AdminLocalization::get(),
            'gettext' => config('admin.gettext', false),
            'locale' => app()->getLocale(),
            'localization' => trans('admin::admin'),
            'dashboard' => $this->getDashBoard(),
            'requests' => [
                'show' => action('\Admin\Controllers\Crud\DataController@show', [':model', ':id', ':subid']),
                'store' => action('\Admin\Controllers\Crud\InsertController@store'),
                'update' => action('\Admin\Controllers\Crud\UpdateController@update'),
                'delete' => action('\Admin\Controllers\Crud\DataController@delete'),
                'getHistory' => action('\Admin\Controllers\HistoryController@getHistory', [':model', ':id']),
                'removeFromHistory' => action('\Admin\Controllers\HistoryController@removeFromHistory'),
                'updateOrder' => action('\Admin\Controllers\Crud\DataController@updateOrder'),
                'buttonAction' => action('\Admin\Controllers\ButtonController@action'),
                'download' => action('\Admin\Controllers\DownloadController@adminDownload'),
                'rows' => action('\Admin\Controllers\LayoutController@getRows', [':table']),
                'translations' => action('\Admin\Controllers\GettextController@getTranslations', [':id', ':table']),
                'switch_locale' => action('\Admin\Controllers\GettextController@switchAdminLanguage', [':id']),
                'update_translations' => action('\Admin\Controllers\GettextController@updateTranslations', [':id', ':table']),
            ],
        ];
    }

    /*
     * Return dashboard content
     */
    private function getDashBoard()
    {
        $dashboard = config('admin.dashboard');

        //Try load blade component
        $path = $dashboard ?: resource_path('views/admin/dashboard.blade.php');
        if (file_exists($path)) {
            return [
                'html' => view()->file($path)->render(),
            ];
        }

        //If vue template is available
        if ( $dashboard ) {
            return [
                'vue' => $dashboard
            ];
        }
    }

    /*
     * Returns paginated rows and all required model informations
     */
    public function getRows($table)
    {
        $model = Admin::getModelByTable($table);
        $isInitialRequest = (int)request('count') == 0;

        //Check if user has allowed model
        if (! $model || ! admin()->hasAccess($model)) {
            Ajax::permissionsError();
        }

        if ( method_exists($model, 'beforeAdminRequest') ){
            $model->beforeAdminRequest();
        }

        //Set parent row into model
        if ( $parentTable = request('parentTable') ){
            $parentRow = Admin::getModelByTable($parentTable)
                                ->withoutGlobalScopes()
                                ->find(request('parentId'));

            if ( $parentRow ) {
                $model->setParentRow($parentRow);
            }
        }

        $data = [];

        //Model tree need to be generated at first order
        //Because we want refresh all fields property by booted session.
        $modelTree = $this->makePage(
            $model,
            false,
            false,
            $isInitialRequest
        );

        //On initial admin request
        if ( $isInitialRequest === true ) {
            $data['model'] = $model->beforeInitialAdminRequest();
        }

        //Add token
        $data['token'] = csrf_token();

        //Add model data
        $data['model'] = array_merge(@$data['model'] ?: [], $modelTree);

        //Add rows data
        $data = array_merge(
            $data,
            (new AdminRows($model, request()))->returnModelData([], $isInitialRequest)
        );

        //Modify intiial request data
        if ( $isInitialRequest === true) {
            $data['model'] = $model->afterInitialAdminRequest($data['model']);

            //We can pass additional data into model
            $data['model']['initial_data'] = $model->getAdminModelInitialData();
        }


        //Download sheet table
        if ( request('download') === true ){
            $sheet = new SheetDownloader($model, $data['rows']);

            if (!($path = $sheet->generate())){
                Ajax::error(_('Tabuľku sa nepodarilo stiahnuť.'), null, null, 500);
            }

            return [
                'download' => (new SecureDownloader($path))->getDownloadPath(true),
            ];
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

        //Translate model fields
        foreach ($fields as $key => $field) {
            $this->updateOptionsForm($key, $field, $fields);

            $this->findEqualOptions($key, $field, $fields);

            foreach ($field as $k => $value) {
                if ( in_array($k, AdminResourcesSyncer::$fieldsTranslatableKeys) ){
                    $fields[$key][$k] = AdminResourcesSyncer::translate($value);
                }
            }
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
            //We need refresh new instance of given model, because on logged user state there may be updated some properties
            $model = $model->newInstance();

            if ($this->skipModelInTree($model)) {
                continue;
            }

            //Check if user has allowed model
            if (! admin()->hasAccess($model, 'read') && ! admin()->hasAccess($model, 'insert')) {
                $model->disableModel = true;
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

    private function isInlineTemplateKey($key)
    {
        $positions = (new Layout)->available_positions;

        return in_array($key, $positions, true);
    }

    /*
     * Return rendered blade layouts
     */
    protected function getLayouts($model)
    {
        $layouts = [];

        $i = 0;
        foreach ((array) $model->getProperty('layouts') as $key => $class) {
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
                $child_model->disableModel = true;
            }

            $child = $child_model === $model ? '$_itself' : $this->makePage($child_model);

            $childs[$child_model->getTable()] = $child;
        }

        $data = [
            'name' => AdminResourcesSyncer::translate($model->getProperty('name')),
            'title' => AdminResourcesSyncer::translate($model->getProperty('title')),
            'icon' => $model->getProperty('icon'),
            'settings' => $model->getModelSettings(),
            'active' => isset($model->disableModel) ? false : $model->getProperty('active'),
            'foreign_column' => $model->getForeignColumn(),
            'without_parent' => $model->getProperty('withoutParent') ?: false,
            'global_relation' => $model->getProperty('globalRelation') ?: false,
            'in_menu' => $model->getProperty('inMenu', false),
            'hidden_tabs' => $model->getProperty('hidden_tabs') ?: [],
            'hidden_groups' => $model->getProperty('hidden_groups') ?: [],
            'reserved' => $this->getModelReversed($model),
            'columns' => $model->getBaseFields(),
            'inParent' => $model->getProperty('inParent') ?: false,
            'single' => $model->getProperty('single') ?: false,
            'minimum' => $model->getProperty('minimum'),
            'maximum' => $model->getProperty('maximum'),
            'insertable' => $model->getProperty('insertable'),
            'editable' => $model->getProperty('editable'),
            'displayable' => $model->getProperty('displayable'),
            'deletable' => $model->getProperty('deletable'),
            'publishable' => $model->getProperty('publishable'),
            'publishableState' => $model->getProperty('publishableState'),
            'sortable' => $model->isSortable(),
            'layouts' => $this->getLayouts($model),
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

    private function getModelReversed($model)
    {
        if ( !($reserved = $model->getProperty('reserved')) ){
            return false;
        }

        return array_filter(array_map(function($id) {
            return (int)$id;
        }, $reserved));
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

   /*
     * Returns all admin languages
     */
    protected function getAdminLanguages()
    {
        if (! Admin::isEnabledAdminLocalization()) {
            return [];
        }

        return AdminLocalization::getLanguages();
    }
}
