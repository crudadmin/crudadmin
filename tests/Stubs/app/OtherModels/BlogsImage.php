<?php

namespace Admin\Tests\App\OtherModels;

use Admin\Models\Model as AdminModel;
use Admin\Fields\Group;

class BlogsImage extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-03 14:11:02';

    /*
     * Template name
     */
    protected $name = 'Images';

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
            'name' => 'name:My image...|type:file|image',
        ];
    }
}