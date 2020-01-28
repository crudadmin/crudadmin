<?php

namespace Admin\Models;

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
        'slug' => 'name:Slug|index',
        'slug_localized' => 'name:Slug localized|type:json',
    ];

    /**
     * Save slug value from model
     *
     * @param  AdminModel  $model
     * @param  mixed  $value
     * @return  void
     */
    public static function snapshot($model, $value = null)
    {
        $value = $model->hasLocalizedSlug()
                    ? json_decode(($value ?: $model->attributes['slug']))
                    : ($value ?: $model->attributes['slug']);

        self::create([
            'table' => $model->getTable(),
            'row_id' => $model->getKey(),
            self::getSlugColumnName($model) => $value,
        ]);
    }

    /*
     * Return used column by model slug localization
     */
    public static function getSlugColumnName($model)
    {
        return $model->hasLocalizedSlug() ? 'slug_localized' : 'slug';
    }
}
