<?php

namespace Gogol\Admin\Tests\App\Models\Articles;

use Gogol\Admin\Fields\Group;
use Gogol\Admin\Models\Model as AdminModel;
use Gogol\Admin\Tests\App\User;

class Tag extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-04 12:10:24';

    /*
     * Template name
     */
    protected $name = 'Tags';

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
            'type' => 'name:Tag type|type:select|options:moovie,blog,article',
            'article' => 'name:Head article|belongsTo:articles,name',
        ];
    }
}