<?php

namespace Admin\Models;

use Admin;
use Carbon\Carbon;

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
    protected $inMenu = false;

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
     * Update permissions titles
     */
    public function setModelPermissions($permissions)
    {
        return [
            'read' => [
                'name' => _('Zobrazovanie histórie'),
                'title' => _('Možnosť zobrázenia zmien pri všetkých záznamoch'),
            ],
            'delete' => [
                'name' => _('Mazanie histórie'),
                'title' => _('Možnosť mazať zmeny v histórii pri všetkych záznamoch'),
                'danger' => true,
            ],
        ];
    }

    /*
     * Modify all request data
     */
    public function convertData($model, $data, $initial = false)
    {
        foreach ($data as $key => $value) {
            if ($value instanceof Carbon) {
                $data[$key] = $value->format('Y-m-d H:i:s');
            }
        }


        foreach ($model->getFields() as $key => $field) {
            //If row is beign created first fime from original params, we need add additional relation data into this
            //original data
            if ( array_key_exists('belongsToMany', $field) ){
                if ( !array_key_exists($key, $data) ) {
                    if ( $initial === true && $relationData = $model->{$key}()->get() ) {
                        $data[$key] = $relationData->pluck('id')->toArray();
                    }
                } else if ( is_array($data[$key]) ) {
                    $data[$key] = array_map(function($id){
                        return (int)$id;
                    }, $data[$key]);
                }
            }
        }

        ksort($data);

        return $data;
    }

    /*
     * Return if field can be skipped in history
     */
    private function canSkipFieldInHistory($model, $key)
    {
        return ! $model->getField($key) || $model->hasFieldParam($key, ['disabled', 'imaginary'], true) || $model->isFieldType($key, 'imaginary');
    }

    /*
     * Compare multidimensional array
     */
    private function array_diff_recursive($array1, $array2)
    {
        $aReturn = [];

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
            if (! array_key_exists($key, $array1)) {
                $aReturn[$key] = $value;
            }
        }

        return $aReturn;
    }

    private function getActualData($model)
    {
        //Dates on frontend are parsed with other method than in admin, we need merge this formats
        $model->setAdminDatesFormat(true);

        $data = $model->attributesToArray();

        $model->setAdminDatesFormat(false);

        return $data;
    }

    /*
     * Compare by last change
     */
    public function checkChanges($model, $data, $oldData)
    {
        $changes = [];

        $actualData = $this->getActualData($model);

        //Get also modified field by mutators, which are not in request
        $data = array_merge($this->array_diff_recursive($actualData, $data), $data);

        //Compare changes
        foreach ($data as $key => $value) {
            if ($this->canSkipFieldInHistory($model, $key)) {
                continue;
            }

            $exists = array_key_exists($key, $oldData);

            if (! $exists && ! is_null($value) || $exists && $oldData[$key] != $value) {
                $changes[$key] = $value;
            }
        }

        //Push empty values into missing keys in actual request
        foreach (array_diff_key($oldData, $data) as $key => $value) {
            if ($this->canSkipFieldInHistory($model, $key)) {
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
        //We need clone given model. Because we cant mutate any field...
        $model = clone $model;

        //We need reset all hidden fields for history
        //We want monitor all fields...
        $model = (clone $model)->setHidden([]);

        foreach (['_id', '_order', '_method', '_model', '_table', '_row_id', 'language_id'] as $key) {
            if (array_key_exists($key, $data)) {
                unset($data[$key]);
            }
        }

        //Modify request data
        $data = $this->convertData($model, $data, $initial);

        $oldData = $model->getHistorySnapshot();

        //If row is editted, but does not exists in db history,
        //then create his initial/original value, and changed value
        if (is_array($original) && count($oldData) == 0) {
            $this->pushChanges($model, $original, null, true);

            $oldData = $this->convertData($model, $original, true);
        }

        //Compare and get new changes
        if ($initial == false) {
            $data = $this->checkChanges($model, $data, $oldData);
        }

        //If no changes
        if (count($data) == 0) {
            return;
        }

        if ($initial === false) {
            $user = admin();
        }

        $snap = [
            'user_id' => ! $initial && $user ? $user->getKey() : null,
            'table' => $model->getTable(),
            'row_id' => $model->getKey(),
            'data' => json_encode($data),
        ];

        //If is initial value
        if ($initial === true) {
            $snap += ['created_at' => $model->created_at];
        }

        $row = $this->newInstance()->forceFill($snap);
        $row->save();

        return $row;
    }

    public function setAdminAttributes($attributes)
    {
        $attributes['changed_fields'] = array_keys((array) json_decode($attributes['data']));

        unset($attributes['data']);

        return array_merge($attributes, $this->relationsToArray());
    }
}
