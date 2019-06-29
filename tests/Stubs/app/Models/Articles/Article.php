<?php

namespace Admin\Tests\App\Models\Articles;

use Admin\Fields\Group;
use Admin\Eloquent\AdminModel;
use Admin\Tests\App\User;

class Article extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-04 12:10:04';

    /*
     * Template name
     */
    protected $name = 'Articles';

    protected $title = 'This is simple model test, with some articles sample data.';

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
            'name' => 'name:Article name|type:string',
            'content' => 'name:Content data|type:text',
            'score' => 'name:Score|type:integer|min:0|max:10|required',
            'image' => 'name:Image|type:file|image',
        ];
    }
}