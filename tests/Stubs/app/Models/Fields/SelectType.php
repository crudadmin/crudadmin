<?php

namespace Admin\Tests\App\Models\Fields;

use Admin\Models\Model as AdminModel;
use Admin\Fields\Group;

class SelectType extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-03 15:13:07';

    /*
     * Template name
     */
    protected $name = 'Select types';

    protected $group = 'fields';

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
            'langs' => 'name:my lang select|belongsTo:model_localizations,name|canAdd',
            'score_input' => 'name:my score select|type:select|options:1,2,3,4,5,6,7,8,9,10|default:8',
            'select_filter_by' => 'name:my filter by select|belongsTo:articles,:name :score|filterBy:score_input,score',
            'comments' => 'name:my filter by auto-table|belongsTo:articles_comments,:name :article_id|filterBy:select_filter_by',
        ];
    }
}