<?php

namespace Gogol\Admin\Models;

use Gogol\Admin\Models\Model;
use Carbon\Carbon;
use Admin;

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

    protected $orderBy = ['id', 'asc'];

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    protected $fields = [
        'table' => 'name:Tabuľka',
        'row_id' => 'name:ID|type:integer|index|unsigned',
        'user' => 'name:Administrator|belongsTo:users,id',
        'data' => 'name:Data|type:text',
    ];

    /*
     * Modify all request data
     */
    public function convertData($model, $data)
    {
        foreach ($data as $key => $value)
        {
            if ( $value instanceof Carbon )
                $data[$key] = $value->format('Y-m-d H:i:00');
        }


        return $data;
    }

    /*
     * Return if field can be skipped in history
     */
    private function canSkipFieldInHistory($model, $key)
    {
        return ! $model->getField($key) || $model->hasFieldParam($key, 'disabled', true);
    }

    /*
     * Compare by last change
     */
    public function checkChanges($model, $data)
    {
        $old_data = $model->getHistorySnapshot();

        $changes = [];

        //Get also modified field by mutators, which are not in request
        $data = array_merge($data, array_diff($model->attributes, $data));

        //Compare changes
        foreach ($data as $key => $value)
        {
            if ( $this->canSkipFieldInHistory($model, $key) )
                continue;

            if ( !array_key_exists($key, $old_data) || $old_data[$key] != $value )
                $changes[$key] = $value;
        }

        //Push empty values into missing keys in actual request
        foreach (array_diff_key($old_data, $data) as $key => $value)
        {
            if ( $this->canSkipFieldInHistory($model, $key) ){
                unset($changes[$key]);
            } else {
                $changes[$key] = is_array($value) ? [] : '';
            }
        }

        return $changes;
    }

    /*
     * Save changes into history
     */
    public function pushChanges($model, $data)
    {
        foreach (['_id', '_order', '_method', '_model', 'language_id'] as $key) {
            if ( array_key_exists($key, $data) )
                unset($data[$key]);
        }

        //Modify request data
        $data = $this->convertData($model, $data);

        //Compare and get new changes
        $data = $this->checkChanges($model, $data);

        //If no changes
        if ( count($data) == 0 )
            return;

        $user = auth()->guard('web')->user();

        return $this->create([
            'user_id' => $user ? $user->getKey() : null,
            'table' => $model->getTable(),
            'row_id' => $model->getKey(),
            'data' => json_encode($data),
        ]);
    }

    public function toArray()
    {
        $attributes = parent::attributesToArray();

        $attributes['changed_fields'] = array_keys((array)json_decode($attributes['data']));

        unset($attributes['data']);

        return array_merge($attributes, $this->relationsToArray());
    }
}