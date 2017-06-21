<?php
namespace Gogol\Admin\Fields\Mutations;

use Fields;
use Admin;

class InterfaceRules
{
    public $attributes = ['in_frontend', 'in_backend', 'in_admin', 'in_console'];

    public function update( $field )
    {
        foreach ($this->attributes as $attribute)
        {
            if ( array_key_exists($attribute, $field) )
            {
                if ( $this->canRegisterRules($attribute) )
                    $field = $this->registerAttributes($field[$attribute], $field);

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
        if ( $type == 'in_frontend' && Admin::isFrontend() )
            return true;

        if ( in_array($type, ['in_backend', 'in_admin']) && Admin::isAdmin() )
            return true;

        if ( $type == 'in_console' && app()->runningInConsole() )
            return true;

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
?>