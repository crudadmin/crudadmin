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
                //If field was deleted from request
                //We does not want update this value.
                if ( $request->isRemovedFieldFromRequest($key) === true ){
                    continue;
                }

                $properties = $model->getRelationProperty($key, 'belongsToMany');

                $previousData = DB::table($properties[3])->where($properties[6], $row->getKey())->get();

                DB::table($properties[3])->where($properties[6], $row->getKey())->delete();

                if (! $request->has($key) || !is_array($request->get($key))) {
                    continue;
                }

                //Add relations
                foreach ($request->get($key) as $key => $id) {
                    if (! is_numeric($id)) {
                        continue;
                    }

                    //We want keep 3rd column previous data
                    $array = (array)(collect($previousData)->firstWhere($properties[7], $id) ?: []);

                    if ( isset($array['id']) ) {
                        unset($array['id']);
                    }

                    $array[$properties[6]] = $row->getKey();
                    $array[$properties[7]] = $id;

                    DB::table($properties[3])->insert($array);
                }
            }
        }
    }
}

?>