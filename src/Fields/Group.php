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

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /*
     * Make full group
     */
    public static function fields(array $fields)
    {
        return (new static($fields))->width('full')->type('own');
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
     * Set width of group
     */
    public function width($width = 'full')
    {
        $this->width = $width;

        return $this;
    }

    /*
     * Set type of group
     */
    public function type($type = 'own')
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
     * Return if is key in inserted group
     */
    private static function isFieldInGroup($key, $group, $field)
    {
        //If is field in group fields list
        if ( array_key_exists($key, $group->fields) )
            return true;

        //If fiels is belongsTo relation, and exists in field list
        if ( array_key_exists(substr($key, 0, -3), $group->fields) )
            return true;

        //if is localization field and exists in field group
        if ( array_key_exists('localization', $field) && array_key_exists(implode(' ', array_slice(explode('_', $key), 0, -1)), $group->fields) )
            return true;
    }

    /*
     * Returns groups of fields with correct order
     */
    public static function build( $model )
    {
        $data = [];

        if ( $groups = \Fields::getFieldsGroups( $model ) )
        {
            foreach ($model->getFields() as $key => $field)
            {
                //If columns is hidden from form
                if ( array_key_exists('removeFromForm', $field) )
                    continue;

                foreach ($groups as $group)
                {
                    //If field is in group,
                    //or field with relationship, or field with localization support
                    if ( self::isFieldInGroup($key, $group, $field) )
                    {
                        //If group does not exists
                        if ( !array_key_exists($group->name, $data) )
                        {
                            $data[ $group->name ] = [
                                'type' => $group->type,
                                'width' => $group->width,
                                'fields' => []
                            ];
                        }

                      $data[ $group->name ]['fields'][] = $key;

                      continue 2;
                    }
                }

                //If column does not exists in any group
                $group = last($data);

                if ( ! $group || $group['type'] != 'default' )
                {
                    $data[] = [
                        'type' => 'default',
                        'fields' => [],
                    ];
                }

                //Add column into last added group
                $data[key( array_slice( $data, -1, 1, TRUE ) )]['fields'][] = $key;
            }
        } else {
            $data[] = [
                'type' => 'default',
                'fields' => array_keys( $model->getFields() ),
            ];
        }

        //Returns groups as non assiociative array
        //becuase javascript does not know order of keys
        $groups = [];
        foreach ($data as $name => $group)
        {
            $groups[] = array_merge($group, ['name' => $name]);
        }

        return $groups;
    }
}
?>