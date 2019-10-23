<?php

namespace Admin\Tests\App\Models\Single;

use Admin\Eloquent\AdminModel;

class SingleModelGroupRelation extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-10-16 11:15:04';

    /*
     * Template name
     */
    protected $name = 'inParent relation';

    protected $single = true;
    protected $inParent = true;
    protected $withoutParent = true;

    protected $belongsToModel = [SingleModel::class, SimpleModel::class];

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
            'name' => 'name:Article name|type:string|required',
            'content' => 'name:Content data|type:text',
            'date' => 'name:datum|type:date|required',
            'file' => 'name:file|type:file|required',
        ];
    }
}
