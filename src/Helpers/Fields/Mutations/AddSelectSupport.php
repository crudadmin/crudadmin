<?php
namespace Gogol\Admin\Helpers\Fields\Mutations;

use DB;
use Admin;
use Localization;

class AddSelectSupport
{
    public $attributes = ['options', 'multiple', 'default'];

    public function update( $field, $key, $model )
    {
        if ( $field['type'] == 'select' && Admin::isAdmin() )
        {
            $options = (array)$model->getProperty('options');

            if ( array_key_exists($key, $options) )
            {
                $field['options'] = $options[$key];
            } else if ( array_key_exists('options', $field) ){
                $field['options'] = is_string($field['options']) ? explode(',', $field['options']) : $field['options'];
            } else if ( array_key_exists('belongsTo', $field) || array_key_exists('belongsToMany', $field) ) {
                $properties = explode(',', array_key_exists('belongsTo', $field) ? $field['belongsTo'] : $field['belongsToMany']);

                //When is defined column which will be in selectbox
                if ( count($properties) >= 2 && strtolower($properties[1]) != 'null' )
                {
                    $options = DB::table($properties[0])->whereNull('deleted_at')->get();

                    $rows = [];

                    foreach ($options as $option)
                    {
                        $option = (array)$option;

                        $key = isset($properties[2]) ? $properties[2] : 'id';

                        if ( array_key_exists('language_id', $option) )
                        {
                            $rows[ $option['language_id'] ][ $option[$key] ] = $option[$properties[1]];
                        } else {
                            $rows[ $option[$key] ] = $option[$properties[1]];
                        }
                    }

                    $field['options'] = $rows;
                }
            }

            //Checks if is non associal array
            if ( array_key_exists('options', $field) )
            {
                //Checks if is array associative
                if ( array_keys($field['options']) === range(0, count($field['options']) - 1) )
                {
                    $field['options'] = array_combine($field['options'], $field['options']);
                }
            } else {
                $field['options'] = [];
            }
        }

        return $field;
    }
}
?>