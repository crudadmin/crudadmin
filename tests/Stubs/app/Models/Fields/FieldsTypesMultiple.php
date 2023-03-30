<?php

namespace Admin\Tests\App\Models\Fields;

use Admin\Eloquent\AdminModel;

class FieldsTypesMultiple extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-03 12:12:04';

    /*
     * Template name
     */
    protected $name = 'Fields types multiple';

    protected $group = 'fields';

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
            'select_multiple' => 'name:my multiple select field|type:select|multiple|options:option a,option b,option c|title:test|required',
            'file_multiple' => 'name:my multiple file field|type:file|title:test|multiple|required',
            'date_multiple' => 'name:my multiple date field|type:date|title:test|multiple|required',
            'time_multiple' => 'name:my time field|type:time|multiple|title:test|required',
        ];
    }
}
