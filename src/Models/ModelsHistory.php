<?php

namespace Admin\Models;

use Admin\Models\Model;
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
        'table' => 'name:Tabuľka|index',
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

        ksort($data);

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
     * Compare multidimensional array
     */
    private function array_diff_recursive($array1, $array2) {
        $aReturn = array();

        foreach ($array1 as $mKey => $mValue) {
            if (array_key_exists($mKey, $array2)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = $this->array_diff_recursive($mValue, $array2[$mKey]);

                    if (count($aRecursiveDiff)) {
                        $aReturn[$mKey] = $aRecursiveDiff;
                    }
                } else {
                    if ($mValue != $array2[$mKey]) {
                        $aReturn[$mKey] = $mValue;
                    }
                }
            } else {
                $aReturn[$mKey] = $mValue;
            }
        }

        //Add missing values from second array
        foreach ($array2 as $key => $value) {
            if ( ! array_key_exists($key, $array1) )
                $aReturn[$key] = $value;
        }

        return $aReturn;
    }

    /*
     * Compare by last change
     */
    public function checkChanges($model, $data, $original = null)
    {
        $old_data = $model->getHistorySnapshot();

        //If row is editted, but does not exists in db history, then create his initial/original value, and changed value
        if ( is_array($original) && count($old_data) == 0 ){
            $this->pushChanges($model, $original, null, true);

            $old_data = $original;
        }

        $changes = [];

        //Get also modified field by mutators, which are not in request
        $data = array_merge($data, $this->array_diff_recursive($model->attributesToArray(), $data));

        //Compare changes
        foreach ($data as $key => $value)
        {
            if ( $this->canSkipFieldInHistory($model, $key) )
                continue;

            $exists = array_key_exists($key, $old_data);

            if ( !$exists && ! is_null($value) || $exists && $old_data[$key] != $value ){
                $changes[$key] = $value;
            }
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
    public function pushChanges($model, $data, $original = null, $initial = false)
    {
        foreach (['_id', '_order', '_method', '_model', 'language_id'] as $key) {
            if ( array_key_exists($key, $data) )
                unset($data[$key]);
        }

        //Modify request data
        $data = $this->convertData($model, $data);

        //Compare and get new changes
        if ( $initial !== true )
            $data = $this->checkChanges($model, $data, $original);

        //If no changes
        if ( count($data) == 0 )
            return;

        if ( $initial === false )
            $user = auth()->guard('web')->user();

        $snap = [
            'user_id' => ! $initial && $user ? $user->getKey() : null,
            'table' => $model->getTable(),
            'row_id' => $model->getKey(),
            'data' => json_encode($data),
        ];

        //If is initial value
        if ( $initial === true )
            $snap += [ 'created_at' => $model->created_at ];

        $row = $this->newInstance()->forceFill($snap);
        $row->save();

        return $row;
    }

    public function setAdminAttributes($attributes)
    {
        $attributes['changed_fields'] = array_keys((array)json_decode($attributes['data']));

        unset($attributes['data']);

        return array_merge($attributes, $this->relationsToArray());
    }
}