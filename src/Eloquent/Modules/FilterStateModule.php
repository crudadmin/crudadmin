<?php

namespace Admin\Eloquent\Modules;

use Admin\Core\Eloquent\Concerns\AdminModelModule;
use Admin\Core\Eloquent\Concerns\AdminModelModuleSupport;
use Arr;

class FilterStateModule extends AdminModelModule implements AdminModelModuleSupport
{
    public function isActive($model)
    {
        return method_exists($model, 'getFilterStates');
    }

    public function setSettingsProperty($settings)
    {
        $filters = [];

        foreach ($this->getModel()->getFilterStates() as $key => $state) {
            $filters[$key] = $state;
        }

        Arr::set($settings, 'rows.filter.items', $filters);

        return $settings;
    }

    public function setAdminRowsAttributes($attributes)
    {
        $states = $this->getModel()->getFilterStates();

        foreach ($states as $state) {
            $active = $state['active'] ?? null;

            if ( is_callable($active) && $active() === true ){
                $attributes['$indicator'] = $state;

                break;
            }
        }

        return $attributes;
    }

    public function scopeFilterProperty($query, $types)
    {
        $types = is_string($types) && strlen($types) > 0 ? explode(',', $types) : [];
        $states = $this->getModel()->getFilterStates();

        foreach ($types as $key) {
            $states[$key]['query']($query);
        }
    }
}
