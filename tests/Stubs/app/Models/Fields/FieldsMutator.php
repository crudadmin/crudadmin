<?php

namespace Admin\Tests\App\Models\Fields;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class FieldsMutator extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-03 15:12:04';

    /*
     * Template name
     */
    protected $name = 'Fields mutators';

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
            'field1' => 'name:field 1|type:string',
            'my_group1' => Group::half([
                'field5' => 'name:my field 5',
                'field6' => 'name:my field 6',
                Group::tab([
                    'field8' => 'name:my field 8',
                    'field9' => 'name:my field 9',
                    'field-rm-1' => 'name:my removed field 1',
                    'field-rm-2' => 'name:my removed field 2',
                    'field-rm-3' => 'name:my removed field 3',
                ]),
            ]),
        ];
    }

    /*
     * Mutate calculator fields
     */
    public function mutateFields($fields)
    {
        $fields->after('field1', [
            'field2' => 'name:field 2|type:string',
            'field3' => 'name:field 3|type:string',
        ]);

        $fields->before('field5', [
            'field4' => 'name:field 4|type:string',
        ]);

        $fields->after('field5', [
            'field4' => 'name:field 4|type:string',
        ]);

        $fields->push([
            'field-end-1' => 'name:field at the end 1|type:string',
            'field-end-2' => 'name:field at the end 2|type:string',
        ]);

        $fields->remove(['field-rm-1', 'field-rm-2']);
        $fields->delete('field-rm-3');
    }
}