<?php

namespace Gogol\Admin\Traits;

use Admin;

trait HasChildrens
{
    /*
     * Automatically and easy assign children relation into model
     */
    protected function checkForChildrenModels($method, $get = false)
    {
        $basename_class = class_basename( get_class($this) );

        $method_singular = strtolower( str_singular($method) );

        //Child model name
        $child_model_name = strtolower( str_plural( $basename_class ) . $method_singular);

        //Check if exists child with model name
        $relation = Admin::hasAdminModel($child_model_name)
                        ? $this->returnAdminRelationship($classname, $get)
                        : null;

        //If is found relation, or if is called relation in singular mode, that means, we don't need hasMany, bud belongsTo relation
        if ( $relation || $method == $method_singular )
            return $relation;

        //Check by last model convention name
        foreach (Admin::getAdminModelsPaths() as $migration_date => $modelname)
        {
            $basename = class_basename($modelname);

            //Check if model ends with needed relation name
            if ( last(explode('_', snake_case($basename))) == $method_singular )
            {
                if ( ($response = $this->returnAdminRelationship(str_plural($basename), $get, [
                    $migration_date => $modelname,
                ])) === false )
                    continue;

                return $response;
            }
        }

        return false;
    }
}