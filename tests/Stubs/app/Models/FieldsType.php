<?php

namespace Gogol\Admin\Tests\App\Models;

use Gogol\Admin\Models\Model as AdminModel;
use Gogol\Admin\Fields\Group;

class FieldsType extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-03 11:10:04';

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
            'string' => 'name:string|type:string',
            'text' => 'name:text|type:text',
            'editor' => 'name:editor|type:editor',
            'select' => 'name:select|type:select',
            'integer' => 'name:integer|type:integer',
            'decimal' => 'name:decimal|type:decimal',
            'file' => 'name:file|type:file',
            'password' => 'name:password|type:password',
            'date' => 'name:date|type:date',
            'datetime' => 'name:datetime|type:datetime',
            'time' => 'name:time|type:time',
            'checkbox' => 'name:checkbox|type:checkbox',
            'radio' => 'name:radio|type:radio',
        ];
    }

    /*
     * Mutate calculator fields
     */
    public function mutateFields($fields)
    {

    }
}