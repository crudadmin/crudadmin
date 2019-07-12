<?php

namespace Admin\Fields\Mutations;

use Admin;
use Fields;
use Admin\Core\Fields\Mutations\MutationRule;

class InterfaceRules extends MutationRule
{
    public $attributes = ['inFrontend', 'inBackend', 'inAdmin', 'inConsole'];

    public function update($field)
    {
        foreach ($this->attributes as $attribute) {
            if (array_key_exists($attribute, $field)) {
                if ($this->canRegisterRules($attribute)) {
                    $field = $this->registerAttributes($field[$attribute], $field);
                }

                unset($field[$attribute]);
            }
        }

        return $field;
    }

    /*
     * Check if fields can be registrated in actual interface
     */
    private function canRegisterRules($type)
    {
        if ($type == 'inFrontend' && Admin::isFrontend()) {
            return true;
        }

        if (in_array($type, ['inBackend', 'inAdmin']) && Admin::isAdmin()) {
            return true;
        }

        if ($type == 'inConsole' && app()->runningInConsole()) {
            return true;
        }

        return false;
    }

    /*
     * Add rules info fields
     */
    private function registerAttributes($rules, $field)
    {
        return $field + Fields::mutate(FieldToArray::class, $rules);
    }
}
