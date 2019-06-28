<?php
namespace Admin\Fields\Mutations;

class AddEmptyValue
{
    public $attributes = 'value';

    public function update( $field )
    {
        $field['value'] = null;

        return $field;
    }
}
?>