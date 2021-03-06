<?php

namespace Admin\Tests\App\Models\Tree;

use Admin\Eloquent\AdminModel;

class Model2 extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-15 12:11:02';

    /*
     * Template name
     */
    protected $name = 'Model 2';

    protected $group = 'level1.level2';

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'field1' => 'name:field 1|required',
        ];
    }
}
