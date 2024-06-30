<?php

namespace Admin\Controllers\Crud\Concerns;

trait CRUDRelations
{
    /*
     * Add/update belongs to many rows into pivot table from selectbox
     */
    protected function syncBelongsToMany($model, $request)
    {
        foreach ($model->getFields() as $key => $field) {
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

            foreach ($values as $row) {
                $isArray = is_array($row);

                $id = $isArray ? $row['id'] : $row;

                if ( $isArray ) {
                    unset($row['id']);
                }

                $pivotData[$id] = $isArray ? $row : [];
            }

            $model->{$key}()->sync($pivotData);
        }
    }
}

?>