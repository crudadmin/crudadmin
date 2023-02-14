<?php

namespace Admin\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Admin;
use Str;

class BelongsToManyCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return array
     */
    public function get($model, $key, $value, $attributes)
    {
        $properties = $model->getRelationProperty($key, 'belongsToMany');

        //Get all admin modules
        $models = Admin::getAdminModelNamespaces();

        foreach ($models as $path) {
            //Find match
            if (strtolower(Str::snake(class_basename($path))) == strtolower($properties[5])) {
                $relations = $model->callWithoutCasts(function() use ($model, $key) {
                    return $model->getValue($key);
                }, $key);

                return $relations->pluck('id')->toArray();
            }
        }
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  array  $value
     * @param  array  $attributes
     * @return string
     */
    public function set($model, $key, $value, $attributes)
    {
        //..
    }
}