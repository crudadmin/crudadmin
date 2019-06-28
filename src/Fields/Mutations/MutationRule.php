<?php

namespace Admin\Fields\Mutations;

class MutationRule
{
    protected $fields;

    protected $field;

    protected $key;

    /*
     * Closure with post update mutation
     * params: $fields, $field, $key, $model
     */
    protected $postUpdate = null;

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

    public function setPostUpdate($closure)
    {
        $this->postUpdate = $closure;
    }

    public function getPostUpdate()
    {
        return $this->postUpdate;
    }
}
?>