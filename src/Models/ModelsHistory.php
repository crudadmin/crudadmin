<?php

namespace Gogol\Admin\Models;

use Gogol\Admin\Models\Model;

class ModelsHistory extends Model
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2017-07-15 00:00:00';

    /*
     * Template name
     */
    protected $name = 'História';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    /*
     * Acivate/deactivate model in administration
     */
    protected $active = false;

    protected $sortable = false;

    protected $publishable = false;

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    protected $fields = [
        'table' => 'name:Tabuľka',
        'row_id' => 'name:ID|type:integer|unsigned',
        'data' => 'name:Data|type:text',
    ];
}