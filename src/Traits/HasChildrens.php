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

        //Child model name
        $child_model_name = strtolower( str_plural( $basename_class ) . str_singular($method));

        //Check if exists child with model name
        $relation = Admin::hasAdminModel($child_model_name, function( $classname ) use ( $get ) {
            return $this->returnAdminRelationship($classname, $get);
        });

        if ( $relation )
            return $relation;

        $method = strtolower( str_singular($method) );
        $method_count = strlen($method);

        //Check by last model convention name
        foreach (Admin::getAdminModelsPaths() as $migration_date => $modelname) {
            $basename = class_basename($modelname);

            //Check if model ends with needed relation name
            if ( substr(strtolower($basename), - $method_count) == $method ){
                return $this->returnAdminRelationship($basename, $get, [
                    $migration_date => $modelname,
                ]);
            }
        }

        return false;
    }
}