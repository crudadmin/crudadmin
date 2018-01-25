<?php

namespace Gogol\Admin\Fields;

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
     * Add items after end
     */
    public $push = [];


    /*
     * Register adding fields after key
     */
    public function after($selector_key, $fields)
    {
        foreach ($fields as $key => $field) {
            $this->after[$selector_key][$key] = $field;
        }
    }

    /*
     * Register adding fields before key
     */
    public function before($selector_key, $fields)
    {
        foreach ($fields as $key => $field) {
            $this->before[$selector_key][$key] = $field;
        }
    }

    /*
     * Remove fields from model
     */
    public function remove($selector_key)
    {
        $this->remove[] = $selector_key;
    }

    /*
     * Remove fields from model
     */
    public function push($fields)
    {
        foreach ($fields as $key => $field) {
            $this->push[$key] = $field;
        }
    }
}
?>