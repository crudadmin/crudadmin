<?php

namespace Gogol\Admin\Models;

use Gogol\Admin\Models\Model;

class SluggableHistory extends Model
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-12-31 00:00:00';

    /*
     * Template name
     */
    protected $name = 'Slug history';

    /*
     * Acivate/deactivate model in administration
     */
    protected $active = false;

    protected $sortable = false;

    protected $publishable = false;

    protected $orderBy = ['id', 'asc'];

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    protected $fields = [
        'table' => 'name:Tabuľka|index',
        'row_id' => 'name:ID|type:integer|index|unsigned',
        'slug' => 'name:Slug',
    ];
}