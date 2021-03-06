<?php

namespace Admin\Fields\Mutations;

use Admin\Core\Fields\Mutations\MutationRule;

class UpdateDateFormat extends MutationRule
{
    public $attributes = ['format', 'date_step'];

    public function update($field)
    {
        if (array_key_exists('format', $field)) {
            $field['date_format'] = $field['format'];
            unset($field['format']);
        }

        if (
            in_array($field['type'], ['date', 'datetime', 'time'])
            && array_key_exists('multiple', $field)
            && $field['multiple'] === true
        ) {
            if ($field['type'] == 'date') {
                $field['date_format'] = 'Y-m-d';
            } elseif ($field['type'] == 'time') {
                $field['date_format'] = 'H:i';
            } else {
                unset($field['multiple']);
            }
        }

        return $field;
    }
}
