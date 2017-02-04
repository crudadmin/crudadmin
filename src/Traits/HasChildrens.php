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
        //Child model name
        $child_model_name = strtolower( str_plural( class_basename( get_class($this) ) ) . str_singular($method));

        return Admin::hasAdminModel( $child_model_name, function( $classname ) use ( $get ) {
            return $this->returnAdminRelationship($classname, $get);
        } );
    }
}