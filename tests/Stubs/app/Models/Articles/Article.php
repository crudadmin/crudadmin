<?php

namespace Gogol\Admin\Tests\App\Models\Articles;

use Gogol\Admin\Fields\Group;
use Gogol\Admin\Models\Model as AdminModel;
use Gogol\Admin\Tests\App\User;

class Article extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-04 12:10:04';

    /*
     * Template name
     */
    protected $name = 'Články';

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
            'name' => 'name:Názov článku|type:string',
            'Parametre' => Group::tab([
                'updated_at' => 'name:Dátum upravenia|type:date',
                'created_at' => 'name:Dátum vytvorenia|type:date',
            ]),
            'Autori článku' => Group::tab(User::class)
        ];
    }
}