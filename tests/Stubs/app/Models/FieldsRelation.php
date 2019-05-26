<?php

namespace Gogol\Admin\Tests\App\Models;

use Gogol\Admin\Fields\Group;
use Gogol\Admin\Models\Model as AdminModel;
use Gogol\Admin\Tests\App\Models\Articles\Article;

class FieldsRelation extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-03 14:12:04';

    /*
     * Template name
     */
    protected $name = 'Fields relations';

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
                'relation1' => 'name:BelongsTo (simple)|belongsTo:articles,name|title:with single column|required',
                'relation2' => 'name:BelongsTo (column)|belongsTo:articles,my option :name :score|title:with builded option by columns|required',
                'relation3' => 'name:BelongsTo (binded data)|belongsTo:articles,my second option :name :score|title:with builded option by columns and given rows|required',
            ]),
            Group::half([
                'relation_multiple1' => 'name:BelongsToMany (simple)|belongsToMany:articles,name|title:with single column|required',
                'relation_multiple2' => 'name:BelongsToMany (column)|belongsToMany:articles,my option :name :score|title:with builded option by columns|required',
                'relation_multiple3' => 'name:BelongsToMany (binded data)|belongsToMany:articles,second option :name :score|title:with builded option by columns and given rows|required',
            ])
        ];
    }

    protected function options()
    {
        $items = Article::take(3)->get()->map(function($item){
            $item->score = $item->score * 2;

            return $item;
        });

        return [
            'relation3_id' => $items,
            'relation_multiple3' => $items,
        ];
    }

    /*
     * Mutate calculator fields
     */
    public function mutateFields($fields)
    {

    }
}