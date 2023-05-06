<?php

namespace Admin\Eloquent\Concerns;

use Admin;
use Carbon\Carbon;

trait Historiable
{
    /*
     * Save actual model row into history
     */
    public function makeHistorySnapshot($request = [], $original = null, $action = 'update')
    {
        if ( $this->getProperty('history') === true ) {
            return $this->pushHistoryChange($request, $original, false, $action);
        } else {
            $this->logHistoryAction($action);
        }
    }

    private function mutateHistoryValue($key, $value)
    {
        if ($this->hasFieldParam($key, 'locale', true)) {
            if (is_string($value)) {
                $value = json_decode($value, true);
            }

            return (array) $value;
        }

        return $value;
    }

    public function getHistoryRows($id = null)
    {
        return Admin::getModel('ModelsHistory')
                ->where('table', $this->getTable())
                ->where('row_id', $id ?: $this->getKey());
    }

    /**
     * Foreach all rows in history, and get acutal data status
     *
     * @param  int|null  $max_id
     * @param  int|null  $id
     * @param  bool  $returnChangesTree
     * @return  array
     */
    public function getHistorySnapshot($max_id = null, $id = null, $returnChangesTree = false)
    {
        $changes = $this->getHistoryRows($id)
                        ->whereIn('action', ['insert', 'update'])
                        ->when($max_id, function ($query, $max_id) {
                            $query->where('id', '<=', $max_id);
                        })
                        ->orderBy('id', 'ASC')
                        ->get();

        if ( $changes->count() == 0 ) {
            return [];
        }

        $versions = [];

        $data = [];
        foreach ($changes as $row) {
            $array = (array) $row['data'];

            foreach ($array as $key => $value) {
                $data[$key] = $this->mutateHistoryValue($key, $value);
            }

            $versions[] = $data;
        }

        //Return all versions tree
        if ( $returnChangesTree === true ){
            return $versions;
        }

        return $data;
    }

    /*
     * Modify all request data
     */
    public function castHistoryData($data, $isMissingHistoryRow = false)
    {
        foreach (['_id', '_order', '_method', '_model', '_table', '_row_id', '_encrypted_hashes', 'language_id'] as $key) {
            if (array_key_exists($key, $data)) {
                unset($data[$key]);
            }
        }

        foreach ($data as $key => $value) {
            if ($value instanceof Carbon) {
                $data[$key] = $value->format('Y-m-d H:i:s');
            }
        }


        foreach ($this->getFields() as $key => $field) {
            //If row is beign created first fime from original params, we need add additional relation data into this
            //original data
            if ( array_key_exists('belongsToMany', $field) ){
                if ( !array_key_exists($key, $data) ) {
                    if ( $isMissingHistoryRow === true && $relationData = $this->{$key}()->get() ) {
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
        if ( ! $model->getField($key) ){
            return true;
        }

        if ( $model->hasFieldParam($key, ['disabled', 'imaginary'], true) ){
            return true;
        }

        if ( $model->isFieldType($key, 'imaginary') ){
            return true;
        }

        return false;
    }

    /*
     * Compare multidimensional array
     */
    private function arrayDiffRecursive($array1, $array2)
    {
        $aReturn = [];

        foreach ($array1 as $mKey => $mValue) {
            if (array_key_exists($mKey, $array2)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = $this->arrayDiffRecursive($mValue, $array2[$mKey]);

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
        foreach ($array2 ?: [] as $key => $value) {
            if (! array_key_exists($key, $array1)) {
                $aReturn[$key] = $value;
            }
        }

        return $aReturn;
    }

    private function getActualData($model)
    {
        $data = $model->attributesToArray();

        return $data;
    }

    /*
     * Compare by last change
     */
    private function checkDataDifferences($model, $data, $oldData)
    {
        $changes = [];

        $actualData = $this->getActualData($model);

        //Get also modified field by mutators, which are not in request
        $data = array_merge($this->arrayDiffRecursive($actualData, $data), $data);

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
    private function pushHistoryChange($updatedRow, $original = null, $isMissingHistoryRow = false, $action = 'update')
    {
        //We need reset all hidden fields for history
        //We want monitor all fields...
        $model = (clone $this)->setHidden([]);

        //Modify request data
        $updatedRow = $this->castHistoryData($updatedRow, $isMissingHistoryRow);

        $oldData = $model->getHistorySnapshot();

        //If row is editted, but does not exists in db history,
        //then create his initial/original value, and changed value
        if (is_array($original) && count($oldData) == 0) {
            $this->pushHistoryChange($original, null, true, $action);

            $oldData = $this->castHistoryData($original, true);
        }

        //Compare and get only new differences after update
        if ($isMissingHistoryRow == false) {
            $updatedRow = $this->checkDataDifferences($model, $updatedRow, $oldData);
        }

        if ( count($updatedRow) === 0 ){
            return;
        }

        return $this->logHistoryAction($action, [
            //If is missing history row, and we are creating new one. We does not want to copy user id
            'user_id' => $isMissingHistoryRow === true ? null : (admin() ? admin()->getKey() : null),
            //If is missing history row, and we want copy created_at from actual row
            'created_at' => $isMissingHistoryRow === true ? $this->created_at : Carbon::now(),
            'data' => $updatedRow,
        ]);
    }

    public function logHistoryAction($action, $data = [])
    {
        //History is disabled
        if ( config('admin.history', false) === false ){
            return;
        }

        $data['action'] = $action;
        $data['user_id'] = $data['user_id'] ?? (admin() ? admin()->getKey() : null);
        $data['created_at'] = ($data['created_at'] ?? null) ?: Carbon::now();
        $data['table'] = $this->getTable();
        $data['row_id'] = $data['row_id'] ?? $this->getKey();
        $data['ip'] = request()->ip();

        return Admin::getModel('ModelsHistory')->create($data);
    }

    public function getEditedHistoryFields()
    {
        if ( $this->history == false ){
            return [];
        }

        $rows = $this->getHistoryRows()->whereIn('action', ['insert', 'update'])->select(['data'])->get();

        $columns = [];

        foreach ($rows as $row) {
            $keys = array_keys($row->data ?: []);

            foreach ($keys as $key) {
                if ( !isset($columns[$key]) ){
                    $columns[$key] = 0;
                }

                $columns[$key] += 1;
            }
        }

        return array_filter($columns, function($count){
            return $count >= 2;
        });
    }
}
