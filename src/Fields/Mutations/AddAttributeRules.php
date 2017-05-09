<?php
namespace Gogol\Admin\Fields\Mutations;

use Fields;

class AddAttributeRules
{
    public function update( $field )
    {
        $custom_rules = config('admin.custom_rules', []);

        //Add custom rules
        foreach ($custom_rules as $rule => $rules)
        {
            if ( array_key_exists($rule, $field) && $field[$rule] == true )
            {
                $rules = Fields::mutate(FieldToArray::class, $rules);

                $field = $field + $rules;

                if ( array_key_exists('type', $rules) )
                    $field['type'] = $rules['type'];
            }
        }

        return $field;
    }
}
?>