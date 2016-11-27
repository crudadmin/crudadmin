<?php
namespace Gogol\Admin\Helpers\Fields\Mutations;

use Fields;

class BelongsToAttributeMutator
{
    public $attributes = ['belongsTo', 'belongsToMany'];

    public function create( $field, $key )
    {
        $add = [];


        if ( array_key_exists('belongsTo', $field) && substr($key, -3) != '_id' )
        {
            $add[ $key . '_id' ] = $field;
        }

        return $add;
    }

    public function remove($field, $key)
    {
        if ( array_key_exists('belongsTo', $field) && substr($key, -3) != '_id' )
        {
            return true;
        }
    }
}
?>