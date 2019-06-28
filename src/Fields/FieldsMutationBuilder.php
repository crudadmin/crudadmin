<?php

namespace Admin\Fields;

class FieldsMutationBuilder
{
    /*
     * Add fields or groups before field
     */
    public $after = [];

    /*
     * Add fields or groups after field
     */
    public $before = [];

    /*
     * Remove fields from array
     */
    public $remove = [];

    /*
     * Add items at the end
     */
    public $push = [];

    /*
     * Add items at the beggining
     */
    public $push_before = [];

    /*
     * Modify group settings
     */
    public $groups = [];

    /*
     * Modify fields
     */
    public $fields = [];

    /*
     * Register adding fields after key
     */
    public function after($selector_key, $fields)
    {
        //Add group
        if ( $fields instanceof Group )
        {
            $this->after[$selector_key][] = $fields;
        }

        //Or set of fields
        else foreach ($fields as $key => $field) {
            if ( is_numeric($key) && $field instanceof Group )
                $this->after[$selector_key][] = $field;
            else
                $this->after[$selector_key][$key] = $field;
        }

        return $this;
    }

    /*
     * Register adding fields before key
     */
    public function before($selector_key, $fields)
    {
        //Add group
        if ( $fields instanceof Group )
        {
            $this->before[$selector_key][] = $fields;
        }

        //Or set of fields
        else foreach ($fields as $key => $field) {
            if ( is_numeric($key) && $field instanceof Group )
                $this->before[$selector_key][] = $field;
            else
                $this->before[$selector_key][$key] = $field;
        }

        return $this;
    }

    /*
     * Remove fields from model
     */
    public function remove($selector_key)
    {
        //Remove multiple fields/groups
        if ( is_array($selector_key) )
            foreach ($selector_key as $key)
                $this->remove[] = $key;

        //Remove single item
        else {
            $this->remove[] = $selector_key;
        }

        return $this;
    }

    /*
     * Added alias for removing/deleting fields/groups
     */
    public function delete($selector_key)
    {
        return $this->remove($selector_key);
    }

    /*
     * Add fields into end of model
     */
    public function push($fields, $type = 'push')
    {
        //Push group or fields
        if ( $fields instanceof Group )
        {
            $this->{$type}[] = $fields;
        }

        //Push fields set
        else foreach ($fields as $key => $field) {
            $this->{$type}[$key] = $field;
        }

        return $this;
    }

    /*
     * Add group modification callback mutator
     */
    public function group($id, $callback)
    {
        return $this->applyMultipleCallbacks($this->groups, $id, $callback);
    }

    /*
     * Add field modification callback mutator
     */
    public function field($key, $callback)
    {
        return $this->applyMultipleCallbacks($this->fields, $key, $callback);
    }

    /*
     * Shortcuts, aliases
     */
    public function pushBefore($selector_key, $fields = null)
    {
        if ( is_null($fields) && (is_array($selector_key) || is_object($selector_key)) )
            return $this->push($selector_key, 'push_before');

        return $this->before($selector_key, $fields);
    }

    public function pushAfter($selector_key, $fields)
    {
        return $this->after($selector_key, $fields);
    }

    public function addBefore($selector_key, $fields)
    {
        return $this->before($selector_key, $fields);
    }

    public function addAfter($selector_key, $fields)
    {
        return $this->after($selector_key, $fields);
    }

    /*
     * Apply single callback or multiple callback from multiple keys
     */
    private function applyMultipleCallbacks(&$property, $key, $callback)
    {
         //Remove multiple fields/groups
        if ( is_array($key) )
            foreach ($key as $k)
                $property[$k] = $callback;

        //Remove single item
        else {
            $property[$key] = $callback;
        }

        return $this;
    }
}
?>