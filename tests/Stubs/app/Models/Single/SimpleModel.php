<?php

namespace Admin\Tests\App\Models\Single;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class SimpleModel extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-10-22 11:10:04';

    /*
     * Template name
     */
    protected $name = 'Model with inParent';

    protected $group = 'single';

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
            'name' => 'name:Article name|type:string',
            'content' => 'name:Content data|type:text|required',
        ];
    }
}
