<?php

namespace Admin\Tests\App\Models\Single;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class SingleModel extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-09-16 11:10:04';

    /*
     * Template name
     */
    protected $name = 'Single model';

    protected $single = true;

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
            'file' => 'name:file|type:file',
            'date' => 'name:datum|type:date',
        ];
    }
}
