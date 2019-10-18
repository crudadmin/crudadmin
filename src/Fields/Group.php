<?php

namespace Admin\Fields;

use Admin\Eloquent\AdminModel;
use Admin\Core\Fields\Group as BaseGroup;

/*
 * Check also available parameters from Admin\Core\Fields\Group
 */
class Group extends BaseGroup
{
    /**
     * Width of group
     *
     * @var  string
     */
    public $width = 'full';

    /**
     * Icon of group
     *
     * @var  string|null
     */
    public $icon = null;

    /**
     * Model of tab group for relationship support
     *
     * @var  string|null
     */
    public $model = null;

    /**
     * Where query for model relationship in tab group
     *
     * @var  Closure
     */
    public $where = null;

    /**
     * Returns icon of group
     *
     * @return  string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Returns model of groups
     *
     * @return  string
     */
    public function getModel()
    {
        if ( ! $this->model )
            return;

        return (new $this->model)->getTable();
    }

    /**
     * Returns where closure for tabs relationships
     *
     * @return Closure
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * Set width of group.
     * @param  string/integer $width full/half/1,2,3,4,5,6,7,8,9,10,11,12
     * @return Group
     */
    public function width($width = 'full')
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Group (font-awesome) icon.
     * @param  string $icon
     * @return Group
     */
    public function icon($icon)
    {
        if (substr($icon, 0, 3) != 'fa-') {
            $icon = 'fa-'.$icon;
        }

        $this->icon = $icon;

        return $this;
    }

    /**
     * Set related admin model as group relation.
     * @param  string $model
     * @return Group
     */
    public function model($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Set width of group.
     * @param  integer/string $width
     * @return Group
     */
    public function grid($width)
    {
        return $this->width($width);
    }

    /**
     * Make group represented as ta.
     * @param  array  $fields
     * @return Group
     */
    public static function tab($fields = [])
    {
        $is_fields = is_array($fields);

        $tab = (new static($is_fields ? $fields : []))->width('full')->type('tab');

        //If tab is relation admin model child
        if (is_string($fields)) {
            $tab->model($fields);
        }

        return $tab;
    }

    /**
     * Make full width group.
     * @param  array  $fields
     * @return Group
     */
    public static function full(array $fields)
    {
        return (new static($fields))->width('full')->type();
    }

    /**
     * Make half width grid group.
     * @param  array  $fields
     * @return Group
     */
    public static function half(array $fields)
    {
        return (new static($fields))->width('half')->type();
    }

    /**
     * Make third width grid group.
     * @param  array  $fields
     * @return Group
     */
    public static function third(array $fields)
    {
        return (new static($fields))->width(4)->type();
    }

    /**
     * Group which will inline all fields in group
     * Fields will be in one row, and not in new row.
     * @param  array  $fields
     * @return Group
     */
    public function inline()
    {
        $this->width = $this->width.'-inline';

        return $this;
    }

    /**
     * Set where query for tab group relationship
     *
     * @param  Closure  $closure
     * @return Group
     */
    public function where(\Closure $closure)
    {
        $this->where = $closure;

        return $this;
    }

    /*
     * Check if group is tab type
     */
    public function isTab()
    {
        return $this->type == 'tab';
    }

    /**
     * Returns groups of fields with correct order.
     * @param  [type] $model [description]
     * @return [type]        [description]
     */
    public static function build(AdminModel $model)
    {
        return \Fields::getFieldsGroups($model);
    }
}
