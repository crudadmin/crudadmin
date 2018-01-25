<?php

namespace Gogol\Admin\Fields;

class Group
{
    public $name = null;

    public $fields = [];

    //Add fields
    public $add = [];

    public $type = 'default';

    public $width = 'full';

    public $icon = null;

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /*
     * Make full group
     * size in 12 cells grid
     */
    public static function fields(array $fields, $size = null, $type = 'default')
    {
        return (new static($fields))->width($size ?: 'full')->type($type);
    }

    /*
     * Make group with full with
     */
    public static function tab(array $fields)
    {
        return (new static($fields))->width('full')->type('tab');
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

    public function icon($icon)
    {
        if ( substr($icon, 0, 3) != 'fa-' )
            $icon = 'fa-'.$icon;

        $this->icon = $icon;

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
     * Set type of group
     */
    public function type($type = 'group')
    {
        $this->type = $type;

        return $this;
    }

    /*
     * Set name of group
     */
    public function name($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /*
     * Push into every field into this group
     */
    public function add($params)
    {
        $this->add[] = $params;

        return $this;
    }

    /*
     * Returns groups of fields with correct order
     */
    public static function build( $model )
    {
        return \Fields::getFieldsGroups( $model );
    }

    public function isTab()
    {
        return $this->type == 'tab';
    }
}
?>