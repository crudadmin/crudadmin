<?php

namespace Admin\Tests\App\OtherModels;

use Admin\Eloquent\AdminModel;

class Blog extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-04 10:10:04';

    /*
     * Template name
     */
    protected $name = 'Blog';

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
            'name' => 'name:string|type:string',
        ];
    }
}
