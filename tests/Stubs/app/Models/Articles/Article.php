<?php

namespace Gogol\Admin\Tests\App\Models\Articles;

use Gogol\Admin\Models\Model as AdminModel;
use Gogol\Admin\Fields\Group;

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
        ];
    }
}