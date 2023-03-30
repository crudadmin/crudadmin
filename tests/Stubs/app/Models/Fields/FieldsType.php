<?php

namespace Admin\Tests\App\Models\Fields;

use Admin\Eloquent\AdminModel;

class FieldsType extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-03 12:02:04';

    /*
     * Template name
     */
    protected $name = 'Fields types';

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
            'string' => 'name:my string field|type:string|title:this is my field description|required',
            'text' => 'name:my text field|type:text|required',
            'editor' => 'name:my editor field|type:editor|required',
            'select' => 'name:my select field|type:select|options:option a,option b|title:test|required',
            'integer' => 'name:my integer field|type:integer|required',
            'decimal' => 'name:my decimal field|type:decimal|required',
            'file' => 'name:my file field|type:file|required',
            'password' => 'name:my password field|type:password|required',
            'date' => 'name:my date field|type:date|required',
            'datetime' => 'name:my datetime field|type:datetime|required',
            'time' => 'name:my time field|type:time|required',
            'checkbox' => 'name:my checkbox field|type:checkbox',
            'radio' => 'name:my radio field|type:radio|options:c,d,b|required',
            'phone' => 'name:phone|type:phone|title:phone title',
            'uploader' => 'name:uploader|type:uploader|title:uploader title',
            'color' => 'name:color|type:color|title:color title',
            'custom' => 'name:my custom field|type:string|component:MyCustomFieldComponent|sub_component:MyCustomFieldSubComponent|required',
            'gutenberg' => 'name:gutenberg|type:gutenberg',
        ];
    }

    /*
     * Mutate calculator fields
     */
    public function mutateFields($fields)
    {
    }
}
