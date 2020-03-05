<?php

namespace Admin\Fields\Mutations;

use Admin;
use Admin\Core\Fields\Mutations\FieldToArray;
use Admin\Core\Fields\Mutations\MutationRule;
use Fields;

class PermissionsSupport extends MutationRule
{
    public $attributes = ['hasAccess', 'hasNotAccess'];

    public function update($field)
    {
        foreach ($this->attributes as $attribute) {
            if (array_key_exists($attribute, $field)) {
                $query = $this->buildQuery($field[$attribute]);

                if ($this->canRegisterRules($attribute, $query)) {
                    $field = $this->registerAttributes($query['attribute'], $field);
                }

                unset($field[$attribute]);
            }
        }

        return $field;
    }

    public function buildQuery($rules)
    {
        $query = explode(',', $rules);

        $accessRule = explode('.', $query[0]);

        return [
            'rules' => [
                'table' => $accessRule[0],
                'permissions' => array_slice($accessRule, 1),
            ],
            'attribute' => implode(',', array_slice($query, 1)),
        ];
    }

    /**
     * Check if user has present all given permissions
     *
     * @param  array  $query
     * @return  bool
     */
    private function hasAllPermissions($query)
    {
        if ( ! admin() ) {
            return false;
        }

        $hasAccess = true;

        //If at least one is not present. User does not have permission to given rules
        foreach ($query['rules']['permissions'] as $permissionKey) {
            if ( admin()->hasAccessByTable($query['rules']['table'], $permissionKey) === false ) {
                $hasAccess = false;
            }
        }

        return $hasAccess;
    }

    /*
     * Check if fields can be registrated in actual interface
     */
    private function canRegisterRules($type, $query)
    {
        if ( $type == 'hasAccess' ) {
            return $this->hasAllPermissions($query);
        }

        else if ($type == 'hasNotAccess') {
            return !$this->hasAllPermissions($query);
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
