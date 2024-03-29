<?php

namespace Admin\Tests\App\Models\Tree;

use Admin\Eloquent\AdminModel;
use Admin\Tests\App\Buttons\SimpleButton;
use Admin\Tests\App\Buttons\QuestionButton;
use Admin\Tests\App\Buttons\TemplateButton;
use Admin\Tests\App\Buttons\SimpleMultipleButton;

class Model1 extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-15 12:10:02';

    /*
     * Template name
     */
    protected $name = 'Model settings';

    protected $group = 'level1';

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
            'field2' => 'name:field 2|required',
            'field3' => 'name:field 3|required',
            'field4' => 'name:field 4|required',
        ];
    }

    protected $settings = [
        'title' => [
            'create' => 'Hlavička nového záznamu',
            'update' => 'Upravujete záznam č. :id, :field1',
        ],
        'buttons' => [
            'create' => 'Vytvoriť nový záznam',
            'insert' => 'Odoslať nový záznam',
            'update' => 'Upraviť starý záznam',
        ],
        'columns' => [
            'field1' => [
                'name' => 'Test field',
                'limit' => 5,
            ],
            'field3.encode' => false,
            'field3.before' => 'field1',
            'field4.after' => 'field1',
            'field5' => [
                'name' => 'My imaginary column',
            ],
        ],
    ];

    public function setAdminAttributes($attributes = [])
    {
        $attributes['field5'] = 'my non existing column';

        return $attributes;
    }

    protected $buttons = [
        SimpleButton::class,
        SimpleMultipleButton::class,
        QuestionButton::class,
        TemplateButton::class,
    ];
}
