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

            //If field was deleted from request
            //We does not want update this value.
            if ( $request->isRemovedFieldFromRequest($key) === true ){
                continue;
            }

            $values = $request->get($key);
            if (!is_array($values)) {
                continue;
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

            $row->{$key}()->sync($pivotData);
        }
    }
}

?>