<?php 
namespace Gogol\Admin\Helpers\Fields\Mutations;

use Fields;

class AddGlobalRules
{
    public function update( $field )
    {
        $global_rules = config('admin.global_rules', []);

        //If is not set field type, default will be string
        if ( !array_key_exists('type', $field) )
        {
            $field['type'] = 'string';
        }

        foreach ($global_rules as $type => $rules)
        {
            if ( $field['type'] == $type )
            {
                $field = $field + Fields::mutate(FieldToArray::class, $rules);
            }
        }

        return $field;
    }
}
?>