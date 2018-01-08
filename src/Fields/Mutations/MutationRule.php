<?php

namespace Gogol\Admin\Fields\Mutations;

class MutationRule
{
    protected $fields;

    protected $field;

    protected $key;

    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    public function setField($field)
    {
        $this->field = $field;
    }

    public function setKey($key)
    {
        $this->key = $key;
    }
}
?>