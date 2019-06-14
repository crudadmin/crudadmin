<?php

namespace Gogol\Admin\Tests\App\Models\Fields;

use Gogol\Admin\Models\Model as AdminModel;
use Gogol\Admin\Fields\Group;

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
            Group::fields([
                'select' => 'name:my select field|type:select|options:option a,option b|required',
                'select_multiple' => 'name:my multiple select field|type:select|multiple|options:option a,option b,option c|required',
            ])->inline(),
            Group::fields([
                'file' => 'name:my file field|type:file|required',
                'file_multiple' => 'name:my multiple file field|type:file|multiple|required',
            ])->inline(),
            Group::fields([
                'date' => 'name:my date field|type:date|required',
                'date_multiple' => 'name:my multiple date field|type:date|multiple|required',
            ])->inline(),
            Group::half([
                'datetime' => 'name:my datetime field|type:datetime|required',
            ]),
            Group::fields([
                'time' => 'name:my time field|type:time|required',
                'time_multiple' => 'name:my time field|type:time|multiple|required',
            ])->inline(),
        ];
    }

    /*
     * Mutate calculator fields
     */
    public function mutateFields($fields)
    {

    }
}