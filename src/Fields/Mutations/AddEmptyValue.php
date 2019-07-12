<?php

namespace Admin\Fields\Mutations;

use Admin\Core\Fields\Mutations\MutationRule;

class AddEmptyValue extends MutationRule
{
    public $attributes = 'value';

    public function update($field)
    {
        $field['value'] = null;

        return $field;
    }
}
