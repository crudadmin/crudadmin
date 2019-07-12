<?php

namespace Admin\Eloquent\Concerns;

use Admin;
use Admin\Models\ModelsHistory;

trait Historiable
{
    /*
     * Save actual model row into history
     */
    public function historySnapshot($request, $original = null)
    {
        return Admin::getModel('ModelsHistory')->pushChanges($this, $request, $original);
    }

    /*
     * Foreach all rows in history, and get acutal data status
     */
    public function getHistorySnapshot($max_id = null, $id = null)
    {
        if (! ($changes = ModelsHistory::where('table', $this->getTable())->where('row_id', $id ?: $this->getKey())->where(function ($query) use ($max_id) {
            if ($max_id) {
                $query->where('id', '<=', $max_id);
            }
        })->orderBy('id', 'ASC')->get())) {
            return [];
        }

        $data = [];

        foreach ($changes as $row) {
            $array = (array) json_decode($row['data']);

            foreach ($array as $key => $value) {
                if ($this->hasFieldParam($key, 'locale', true)) {
                    if (is_string($value)) {
                        $value = json_decode($value);
                    }

                    $value = (array) $value;
                }

                $data[$key] = $value;
            }
        }

        return $data;
    }
}
