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
                    //If group does not exists
                    if ( !array_key_exists($group->name, $data) )
                    {
                        $data[ $group->name ] = [
                            'type' => $group->type,
                            'width' => $group->width,
                            'fields' => []
                        ];
                    }

                    if ( array_key_exists($key, $group->fields) )
                    {
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

                $data[key( array_slice( $data, -1, 1, TRUE ) )]['fields'][] = $key;
            }
        } else {
            $data[] = [
                'type' => 'default',
                'fields' => array_keys( $model->getFields() ),
            ];
        }

        return $data;
    }
}
?>