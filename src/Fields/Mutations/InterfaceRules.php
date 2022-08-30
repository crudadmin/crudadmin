<?php

namespace Admin\Fields\Mutations;

use Admin;
use Admin\Core\Fields\Mutations\FieldToArray;
use Admin\Core\Fields\Mutations\MutationRule;
use Fields;

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
        $rules = array_wrap($rules);

        //Support multiple rules in one field
        foreach ($rules as $rule) {
            $field = $field + Fields::mutate(FieldToArray::class, $rule);
        }

        return $field;
    }
}
