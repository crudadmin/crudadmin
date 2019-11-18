<?php

namespace Admin\Controllers\Crud\Concerns;

use DB;

trait CRUDRelations
{
    /*
     * Add/update belongs to many rows into pivot table from selectbox
     */
    protected function updateBelongsToMany($model, $row, $request)
    {
        foreach ($model->getFields() as $key => $field) {
            if (array_key_exists('belongsToMany', $field)) {
                $properties = $model->getRelationProperty($key, 'belongsToMany');

                DB::table($properties[3])->where($properties[6], $row->getKey())->delete();

                if (! $request->has($key)) {
                    continue;
                }

                //Add relations
                foreach ($request->get($key) as $key => $id) {
                    if (! is_numeric($id)) {
                        continue;
                    }

                    $array = [];
                    $array[$properties[6]] = $row->getKey();
                    $array[$properties[7]] = $id;

                    DB::table($properties[3])->insert($array);
                }
            }
        }
    }
}

?>