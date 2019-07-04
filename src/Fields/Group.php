<?php

namespace Admin\Fields;

use Admin\Core\Fields\Group as BaseGroup;

class Group extends BaseGroup
{
    public $name = null;

    public $width = 'full';

    public $icon = null;

    public $model = null;

    /*
     * Set name of group
     */
    public function name($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /*
     * Group icon
     */
    public function icon($icon)
    {
        if ( substr($icon, 0, 3) != 'fa-' )
            $icon = 'fa-'.$icon;

        $this->icon = $icon;

        return $this;
    }

    /*
     * Set id of group
     */
    public function id($id)
    {
        $this->id = $id;

        return $this;
    }

    /*
     * Push parameters into every field in group
     */
    public function add($params)
    {
        $this->add[] = $params;

        return $this;
    }

    /*
     * Set width of group
     */
    public function width($width = 'full')
    {
        $this->width = $width;

        return $this;
    }

    /*
     * Set width of group
     */
    public function grid($width)
    {
        return $this->width($width);
    }

    /*
     * Set related model as group
     */
    public function model($model)
    {
        $this->model = (new $model)->getTable();

        return $this;
    }

    /*
     * Make full group
     * size in 12 cells grid
     */
    public static function fields(array $fields = [], $size = null, $type = 'default')
    {
        return (new static($fields))->width($size ?: 'full')->type($type);
    }

    /*
     * Make group represented as tab
     */
    public static function tab($fields = [])
    {
        $is_fields = is_array($fields);

        $tab = (new static($is_fields ? $fields : []))->width('full')->type('tab');

        //If tab is relation admin model child
        if ( is_string($fields) ){
            $tab->model($fields);
        }

        return $tab;
    }

    /*
     * Make group with full with
     */
    public static function full(array $fields)
    {
        return (new static($fields))->width('full')->type();
    }

    /*
     * Make group with half of width in grid
     */
    public static function half(array $fields)
    {
        return (new static($fields))->width('half')->type();
    }

    /*
     * Make group with half of width in grid
     */
    public static function third(array $fields)
    {
        return (new static($fields))->width(4)->type();
    }

    /*
     * Make group with auto inline grid style
     */
    public function inline()
    {
        $this->width = $this->width . '-inline';

        return $this;
    }

    public function isTab()
    {
        return $this->type == 'tab';
    }

    /*
     * Returns groups of fields with correct order
     */
    public static function build( $model )
    {
        return \Fields::getFieldsGroups( $model );
    }
}
?>