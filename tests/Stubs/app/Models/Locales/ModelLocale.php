<?php

namespace Gogol\Admin\Tests\App\Models\Locales;

use Gogol\Admin\Models\Model as AdminModel;
use Gogol\Admin\Fields\Group;

class ModelLocale extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-07-13 15:06:05';

    /*
     * Template name
     */
    protected $name = 'Locales';

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
            Group::half([
                'string' => 'name:my string field|type:string||required|locale',
                'text' => 'name:my text field|type:text|required|locale',
                'editor' => 'name:my editor field|type:editor|required|locale',
                'select' => 'name:my select field|type:select|options:option a,option b|required|locale',
            ]),
            Group::half([
                'integer' => 'name:my integer field|type:integer|locale|required',
                'decimal' => 'name:my decimal field|type:decimal|locale|required',
                'file' => 'name:my file field|type:file|required|locale',
                'date' => 'name:my date field|type:date|required|locale',
                'datetime' => 'name:my datetime field|type:datetime|required|locale',
                'time' => 'name:my time field|type:time|required|locale',
                'checkbox' => 'name:my checkbox field|type:checkbox|locale',
                'radio' => 'name:my radio field|type:radio|options:c,d,b|required|locale',
            ])
        ];
    }
}