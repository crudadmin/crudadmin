<?php

namespace Gogol\Admin\Tests\App\Models;

use Gogol\Admin\Models\Model as AdminModel;
use Gogol\Admin\Fields\Group;

class ModelLocalization extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-07-13 15:05:04';

    /*
     * Template name
     */
    protected $name = 'Localization';

    protected $localization = true;

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
            'name' => 'name:Name|required',
        ];
    }
}