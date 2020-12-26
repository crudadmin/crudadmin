<?php

namespace Admin\Eloquent\Concerns;

use Admin;
use Admin\Models\ModelsHistory;
use Carbon\Carbon;

trait Historiable
{
    /*
     * Save actual model row into history
     */
    public function historySnapshot($request = [], $original = null)
    {
        return Admin::getModel('ModelsHistory')->pushChanges($this, $request, $original);
    }

    private function mutateHistoryValue($key, $value)
    {
        if ($this->hasFieldParam($key, 'locale', true)) {
            if (is_string($value)) {
                $value = json_decode($value);
            }

            return (array) $value;
        }

        return $value;
    }

    /**
     * Foreach all rows in history, and get acutal data status
     *
     * @param  int|null  $max_id
     * @param  int|null  $id
     * @param  bool  $returnChangresTree
     * @return  array
     */
    public function getHistorySnapshot($max_id = null, $id = null, $returnChangresTree = false)
    {
        if (! ($changes = ModelsHistory::where('table', $this->getTable())->where('row_id', $id ?: $this->getKey())->where(function ($query) use ($max_id) {
            if ($max_id) {
                $query->where('id', '<=', $max_id);
            }
        })->orderBy('id', 'ASC')->get())) {
            return [];
        }

        $versions = [];

        $data = [];
        foreach ($changes as $row) {
            $array = (array) json_decode($row['data']);

            foreach ($array as $key => $value) {
                $data[$key] = $this->mutateHistoryValue($key, $value);
            }

            $versions[] = $data;
        }

        //Return all versions tree
        if ( $returnChangresTree === true ){
            return $versions;
        }

        return $data;
    }
}
