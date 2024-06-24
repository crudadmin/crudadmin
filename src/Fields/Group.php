<?php

namespace Admin\Fields;

use Admin;
use Admin\Core\Fields\Group as BaseGroup;
use Admin\Core\Fields\Mutations\FieldToArray;
use Admin\Eloquent\AdminModel;
use Admin\Models\SiteBuilder;
use Fields;

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
     * Model table name of tab group for relationship support
     *
     * @var  string|null
     */
    public $model = null;

    /**
     * Namespace of model relation
     *
     * @var  string|null
     */
    public $modelNamespace = null;

    /**
     * Where query for model relationship in tab group
     *
     * @var  Closure
     */
    public $where = null;

    /**
     * Group attributes
     *
     * @var  array
     */
    public $attributes = [];

    /**
     * Set custom tab component
     *
     * @var  string
     */
    public $component = null;

    /*
     * Forward methods to support nonstatic/stattic...
     * origMethodName => alias
     */
    protected $forwardCalls = [
        'inline' => '_inlineStatic',
        'component' => '_componentStatic',
    ];

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
        if ( ! $this->modelNamespace || ! class_exists($this->modelNamespace) ) {
            return;
        }

        //If table name is cached already
        if ( $this->model ) {
            return $this->model;
        }

        //Cache table name
        return $this->model = (new $this->modelNamespace)->getTable();
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
        //Add inline property into existing inline group
        if ( strpos($this->width, '-inline') ) {
            $width .= '-inline';
        }

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
        $this->modelNamespace = $model;

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
     * Create sitebuilder tab
     *
     * @return  Group
     */
    public static function builder()
    {
        if ( Admin::isEnabledSitebuilder() ) {
            return self::tab(SiteBuilder::class)->icon('fa-th')->id('sitebuilder');
        }

        return [];
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
     * Set attributes for given group
     *
     * @param  string|array  $attributes
     *
     * @return  Group
     */
    public function attributes($attributes)
    {
        $this->attributes = Fields::mutate(FieldToArray::class, $attributes);

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

    /*
     * Build group class
     */
    public function build()
    {
        //Set model table name into group
        $this->getModel();

        return $this;
    }

    /**
     * Group which will inline all fields in group
     * Fields will be in one row, and not in new row.
     * ->inline or ::inline is working
     *
     * @param  array  $fields
     * @return Group
     */
    public function _inlineStatic(array $fields = null)
    {
        if ( is_array($fields) && count($fields) ){
            $this->fields = $fields;
        }

        return $this->width($this->width.'-inline');
    }

    /**
     * Set component of the tab
     * ->component or ::component are working
     *
     * @param  string  $prefix
     * @return  Group
     */
    public function _componentStatic(string $component)
    {
        $this->component = $component;

        return $this;
    }
}
