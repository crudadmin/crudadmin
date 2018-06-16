<?php
namespace Gogol\Admin\Fields\Mutations;

class UpdateDateFormat
{
    public $attributes = ['format', 'date_step'];

    public function update( $field )
    {
        if ( array_key_exists('format', $field) )
        {
            $field['date_format'] = $field['format'];
            unset($field['format']);
        }

        if (
            in_array($field['type'], ['date', 'datetime', 'time'])
            && array_key_exists('multiple', $field)
            && $field['multiple'] === true
        )
        {
            if ( $field['type'] == 'date' ){
                $field['date_format'] = 'Y-m-d';
            } else if ( $field['type'] == 'time' ) {
                $field['date_format'] = 'H:i';
            } else {
                unset($field['multiple']);
            }
        }

        return $field;
    }
}
?>