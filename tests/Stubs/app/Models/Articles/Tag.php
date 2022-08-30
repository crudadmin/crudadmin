<?php

namespace Admin\Tests\App\Models\Articles;

use Admin\Eloquent\AdminModel;

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
            'article' => 'name:Head article|belongsTo:articles,:name :imaginary_column',
        ];
    }

    public function options()
    {
        return [
            'article_id' => Article::all()->map(function($item){
                return $item->toArray() + [
                    'imaginary_column' => 1,
                ];
            }),
        ];
    }
}
