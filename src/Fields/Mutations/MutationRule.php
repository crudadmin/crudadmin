<?php

namespace Gogol\Admin\Fields\Mutations;

class MutationRule
{
    protected $fields;

    protected $field;

    protected $key;

    protected $post_update = [];

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

    public function getKey()
    {
        return $this->key;
    }

    public function addPostUpdate($closure)
    {
        $this->post_update[] = $closure;
    }

    public function getPostUpdate()
    {
        return $this->post_update;
    }
}
?>