<?php

namespace Admin\Eloquent\Modules;

use Admin\Core\Eloquent\Concerns\AdminModelModule;
use Admin\Core\Eloquent\Concerns\AdminModelModuleSupport;

class HistoryModule extends AdminModelModule implements AdminModelModuleSupport
{
    public function isActive($model)
    {
        return $model->getProperty('history') == true;
    }

    public function setAdminRowAttributes($attributes)
    {
        $attributes['$historyChanges'] = $this->getModel()->getEditedHistoryFields();

        return $attributes;
    }
}
