<?php

namespace Gogol\Admin\Tests\App\Models;

use Gogol\Admin\Models\Model as AdminModel;
use Gogol\Admin\Fields\Group;

class FieldsLevel extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-03 11:11:02';

    /*
     * Template name
     */
    protected $name = 'Fields level';

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
                'field2' => 'name:my field 2',
                'field3' => 'name:my field 3',
                Group::tab([
                    'field4' => 'name:my field 4',
                    'field5' => 'name:my field 5',
                ]),
                'field6' => 'name:my field 6',
                'field7' => 'name:my field 7',
                Group::tab([
                    'field8' => 'name:my field 8',
                    'field9' => 'name:my field 8',
                    'field10' => 'name:my field 8',
                ]),
            ]),
            'my_group2' => Group::fields([
                'field11' => 'name:my field 2',
                'field12' => 'name:my field 3',
                Group::tab([
                    'field13' => 'name:my field 4',
                    'field14' => 'name:my field 5',
                ])->id('my_tab')->icon('my_icon'),
                'field15' => 'name:my field 6',
                'field17' => 'name:my field 7',
                Group::tab([
                    'field17' => 'name:my field 8',
                    'field18' => 'name:my field 8',
                    'field19' => 'name:my field 8',
                    Group::third([
                        'field20' => 'name:my field 8',
                        'field21' => 'name:my field 8',
                        Group::full([
                            'field22' => 'name:my field 8',
                            'field23' => 'name:my field 8',
                        ]),
                        Group::full([
                            'field24' => 'name:my field 8',
                            'field25' => 'name:my field 8',
                        ])->inline(),
                    ]),
                ]),
            ]),
            'my_group3' => Group::fields([
                'field26' => 'name:my field 8',
            ])->width(6),
            'my_group4' => Group::fields([
                'field26' => 'name:my field 8',
            ])->grid(6),
        ];
    }

    /*
     * Mutate calculator fields
     */
    public function mutateFields($fields)
    {

    }
}