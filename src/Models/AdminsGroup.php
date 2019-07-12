<?php

namespace Admin\Models;

use Admin;
use Admin\Eloquent\AdminModel;

class AdminsGroup extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2016-07-09 17:00:00';

    /*
     * Template name
     */
    protected $name = 'admin::admin.user-groups';

    /*
     * Template title
     * Default ''
     */
    protected $title = 'admin::admin.user-groups-title';

    /*
     * Group
     */
    protected $group = 'settings';

    /*
     * Disabled publishing
     */
    protected $publishable = false;

    /*
     * Disabled sorting
     */
    protected $sortable = false;

    /*
     * Model icon
     */
    protected $icon = 'fa-universal-access';

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/data/checkbox
     * ... other validation methods from laravel
     */
    protected $fields = [
        'name' => 'name:admin::admin.user-groups-name|placeholder:admin::admin.user-groups-placeholder|type:string|required|max:90',
        'models' => 'name:admin::admin.user-groups-modules|type:select|multiple|limit:40',
    ];

    /*
     * Build models tree by relationship parents
     */
    private function buildModelTree($model, $tree = [])
    {
        $parents = $model->getBelongsToRelation();
        $parents_basename = $model->getBelongsToRelation(true);

        //If has no more levels, or is recursive model
        if (count($parents) == 0 || in_array(class_basename(get_class($model)), $parents_basename)) {
            $tree[] = $model->getProperty('name');

            return implode(' > ', array_reverse($tree));
        }

        $parent = new $parents[0];

        $tree[] = $model->getProperty('name');

        return $this->buildModelTree($parent, $tree);
    }

    public function options()
    {
        $models = Admin::getAdminModelNamespaces();

        $options = [];

        foreach ($models as $migration => $path) {
            $model = new $path;

            if ($model->getProperty('active') === true) {
                $options[$path] = $this->buildModelTree($model, []);
            }
        }

        setlocale(LC_COLLATE, 'sk_SK.utf8');

        uasort($options, 'strcoll');

        return [
            'models' => $options,
        ];
    }
}
