<?php

namespace Admin\Controllers\Crud\Concerns;

trait CRUDRelations
{
    /*
     * Add/update belongs to many rows into pivot table from selectbox
     */
    protected function syncBelongsToMany($row, $request)
    {
        foreach ($row->getFields() as $key => $field) {
            if (!array_key_exists('belongsToMany', $field)) {
                continue;
            }

            if ( $request->isFieldWhitelisted($key) === false ){
                continue;
            }

            //If field was deleted from request
            //We does not want update this value.
            if ( $request->isRemovedFieldFromRequest($key) === true ){
                continue;
            }

            if ( ($pivotData = $this->getPivotData($key, $request)) !== false ) {
                $row->getAdminRules(function ($rule) use (&$pivotData, $key, $row) {
                    $method = 'set'.$key.'relation';

                    if (method_exists($rule, $method)) {
                        $pivotData = $rule->{$method}($row, $pivotData);
                    }
                });

                $row->{$key}()->sync($pivotData);
            }
        }
    }

    private function getPivotData($key, $request = null)
    {
        $values = ($request ?: request())->get($key);

        //Test to cast as array
        if ( is_string($values) ){
            $values = json_decode($values, true);
        }

        if (!is_array($values)) {
            return false;
        }

        $pivotData = [];

        foreach ($values as $item) {
            $isArray = is_array($item);

            $id = $isArray ? $item['id'] : $item;

            if ( $isArray ) {
                unset($item['id']);
            }

            //Pass additional pivot data, or pass noting in case of simple id
            $pivotData[$id] = $isArray ? $item : [];
        }

        return $pivotData;
    }
}

?>