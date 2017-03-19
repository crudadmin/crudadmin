<?php
namespace Gogol\Admin\Helpers\Fields\Mutations;

class UpdateDateFormat
{
    public $attributes = 'format';

    public function update( $field )
    {
        if ( array_key_exists('format', $field) )
        {
            $field['date_format'] = $field['format'];
            unset($field['format']);
        }

        return $field;
    }
}
?>