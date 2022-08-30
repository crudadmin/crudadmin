<?php

namespace Admin\Eloquent\Modules;

use Admin\Core\Eloquent\Concerns\AdminModelModule;
use Admin\Core\Eloquent\Concerns\AdminModelModuleSupport;
use Admin\Fields\Group;
use Illuminate\Validation\Rule;
use Admin;

class GlobalRelationModule extends AdminModelModule implements AdminModelModuleSupport
{
    public function boot()
    {
        /*
         * Hide meta fields
         */
        if ( Admin::isFrontend() ) {
            $this->getModel()->makeHidden([
                '_table',
                '_row_id',
            ]);
        }
    }

    public function isActive($model)
    {
        return $model->getProperty('globalRelation') === true;
    }

    public function mutateFields($fields, $row)
    {
        $fields->pushBefore([
            '_table' => 'name:Table|index|invisible|keepInRequest',
            '_row_id' => 'name:Row|index|type:integer|unsigned|invisible|keepInRequest',
        ]);
    }
}
