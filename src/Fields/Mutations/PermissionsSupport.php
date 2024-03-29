<?php

namespace Admin\Fields\Mutations;

use Admin;
use Admin\Core\Fields\Mutations\FieldToArray;
use Admin\Core\Fields\Mutations\MutationRule;
use Fields;

class PermissionsSupport extends MutationRule
{
    /**
     * Usage:
     *
     * hasAccess:permission_name_of_actual_table,attribute
     * hasAccess:other_table.permission_name,attributeA
     * hasAccess:other_table.permission_name,attributeA,attributeB      => access to given permission key with multiple attrs
     * hasAccess:*,attributeA                                           => full access to actual model
     * hasAccess:other_table.*,attributeA,attributeB                    => full access to given model table
     */
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

        //If table is missing. Use actual models permissions key
        if ( count($accessRule) == 1 ) {
            $accessRule = [$this->getModel()->getTable(), $accessRule[0]];
        }

        //If table and model is the same. We want get namespace of actual model
        if ( $accessRule[0] === $this->getModel()->getTable() ) {
            $classname = get_class($this->getModel());
        }

        //We need find model by table. Which is other by actual model.
        else if ( $model = Admin::getModelByTable($accessRule[0]) ) {
            $classname = get_class($model);
        }

        //Model has not been found, or is not bootstraped yet.
        else {
            $classname = null;
        }

        $attributes = implode('|', array_slice($query, 1));

        return [
            'rules' => [
                'classname' => $classname,
                'permissions' => array_slice($accessRule, 1),
            ],
            'attribute' => $attributes,
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
            if ( admin()->hasAccess($query['rules']['classname'], $permissionKey) === false ) {
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
