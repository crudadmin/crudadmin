<?php

namespace Admin\Tests\App\Models\Tree;

use Admin\Fields\Group;
use Admin\Eloquent\AdminModel;
use Admin\Tests\App\Layouts\FormTopLayout;

class Model3 extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-15 12:12:02';

    /*
     * Template name
     */
    protected $name = 'Model layouts';

    protected $group = 'level1.level2.level3';

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
            'field2' => 'name:field 2',
            'field3' => 'name:field 3',
            'field4' => 'name:field 4',
        ];
    }

    protected $layouts = [
        'top' => 'TopLayout.vue',
        'bottom' => 'BottomLayout.vue',
        FormTopLayout::class,
        'form-bottom' => 'FormBottomLayout.vue',
        'form-header' => 'FormHeaderLayout.vue',
        'form-footer' => 'FormFooterLayout.vue',
        'table-header' => 'TableHeaderLayout.vue',
        'table-footer' => 'TableFooterLayout.vue',
    ];
}