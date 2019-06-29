<?php

namespace Admin\Tests\App\Models\Articles;

use Admin\Fields\Group;
use Admin\Eloquent\AdminModel;
use Admin\Tests\App\User;

class ArticlesComment extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-04 12:10:15';

    /*
     * Template name
     */
    protected $name = 'Comments';

    protected $title = 'This is comments relation test.';

    protected $belongsToModel = Article::class;

    protected $sortable = false;

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
            'name' => 'name:Comment|type:string',
        ];
    }
}