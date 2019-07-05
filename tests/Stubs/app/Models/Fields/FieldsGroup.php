<?php

namespace Admin\Tests\App\Models\Fields;

use Admin\Fields\Group;
use Admin\Eloquent\AdminModel;

class FieldsGroup extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-03 11:11:02';

    /*
     * Template name
     */
    protected $name = 'Fields groups & tabs';

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
            'field1' => 'name:field 1|required',
            'my_group1' => Group::half([
                'my tab 1' => Group::tab([
                    'field2' => 'name:my field 2|required',
                    'field3' => 'name:my field 3|required',
                ])->icon('fa-gift'),
                'my tab 2' => Group::tab([
                    'field4' => 'name:my field 4|required',
                    'field5' => 'name:my field 5|required',
                ])->icon('fa-truck'),
                'field6' => 'name:my field 6|required',
                'field7' => 'name:my field 7|required',
                'field8' => 'name:my field 8|required',
                'field9' => 'name:my field 9|required',
            ])->icon('fa-gear'),
            'my_group2' => Group::half([
                Group::tab([
                    'field10' => 'name:my field 10|required',
                    'field11' => 'name:my field 11|required',
                ])->id('my_tab')->icon('fa-gifht')->name('my tab 3'),
                'my tab 4' => Group::tab([
                    'field12' => 'name:my field 12|required',
                    'field13' => 'name:my field 13|required',
                    Group::third([
                        'field14' => 'name:my field 14|required',
                        'field15' => 'name:my field 15|required',
                        Group::full([
                            'field16' => 'name:my field 16|required',
                            'field17' => 'name:my field 17|required',
                        ])->inline(),
                    ]),
                    'my_group3' => Group::fields([
                        'field18' => 'name:my field 18|required',
                        'field19' => 'name:my field 19|required',
                    ])->width(8),
                    'my_group4' => Group::fields([
                        'field20' => 'name:my field 20|required',
                        'field21' => 'name:my field 21|required',
                    ])->grid(12),
                    'my tab 5' => Group::tab([
                        'field22' => 'name:my field 22|required',
                        'field23' => 'name:my field 23|required',
                    ]),
                    'my tab 6' => Group::tab([
                        'field24' => 'name:my field 24|required',
                        'field25' => 'name:my field 25|required',
                    ]),
                ]),
                'field26' => 'name:my field 26|required',
                'field27' => 'name:my field 27|required',
                'field28' => 'name:my field 28|required',
            ]),
        ];
    }

    /*
     * Mutate calculator fields
     */
    public function mutateFields($fields)
    {

    }
}