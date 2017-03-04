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
    protected $name = 'Používateľské skupiny';

    /*
     * Template title
     * Default ''
     */
    protected $title = 'Upravte používateľské skupiny do ktorých budu následne priradený administrátori';

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
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/data/checkbox
     * ... other validation methods from laravel
     */
    protected $fields = [
        'name' => 'name:Názov skupiny|placeholder:Zadajte názov skupiny|type:string|required|max:90',
        'models' => 'name:Povolené moduly|type:select|multiple',
    ];

    public function options()
    {
        $models = Admin::getAdminModelsPaths();

        $options = [];

        foreach ($models as $migration => $path)
        {
            $model = new $path;

            $options[ $path ] = $model->getProperty('name');
        }

        return [
            'models' => $options,
        ];
    }

}