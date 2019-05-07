<?php

namespace Gogol\Admin\Tests\App\Models;

use Gogol\Admin\Models\Model as AdminModel;
use Gogol\Admin\Fields\Group;

class FieldsType extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-03 12:02:04';

    /*
     * Template name
     */
    protected $name = 'Field types';

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
            'string' => 'name:my string field|type:string',
            'text' => 'name:my text field|type:text',
            'editor' => 'name:my editor field|type:editor',
            'select' => 'name:my select field|type:select',
            'integer' => 'name:my integer field|type:integer',
            'decimal' => 'name:my decimal field|type:decimal',
            'file' => 'name:my file field|type:file',
            'password' => 'name:my password field|type:password',
            'date' => 'name:my date field|type:date',
            'datetime' => 'name:my datetime field|type:datetime',
            'time' => 'name:my time field|type:time',
            'checkbox' => 'name:my checkbox field|type:checkbox',
            'radio' => 'name:my radio field|type:radio',
        ];
    }

    /*
     * Mutate calculator fields
     */
    public function mutateFields($fields)
    {

    }
}