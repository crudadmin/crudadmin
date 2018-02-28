<?php

namespace Gogol\Admin\Models;

use Gogol\Admin\Models\Model as AdminModel;
use Admin;

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

    public function options()
    {
        $models = Admin::getAdminModelsPaths();

        $options = [];

        foreach ($models as $migration => $path)
        {
            $model = new $path;

            if ( $model->getProperty('active') === true )
                $options[ $path ] = $model->getProperty('name');
        }

        return [
            'models' => $options,
        ];
    }

}