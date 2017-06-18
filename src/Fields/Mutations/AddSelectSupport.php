<?php
namespace Gogol\Admin\Fields\Mutations;

use DB;
use Admin;
use Localization;
use Gogol\Admin\Helpers\Helper;
use Illuminate\Support\Collection;
use Ajax;

class AddSelectSupport
{
    public $attributes = ['options', 'multiple', 'default'];

    /*
     * If has key in admin buffer, returns data from buffer, if has not, then get data from database and save into buffer
     */
    protected function getOptionsFromBuffer($key, $data)
    {
        if ( Admin::has( $key ) )
        {
            return Admin::get($key);
        }

        $options = call_user_func($data);

        return Admin::save($key, $options);
    }

    /*
     * Check if is array associative
     */
    protected function isAssoc(array $arr)
    {
        if ([] === $arr)
            return false;

        if ( array_keys($arr) !== range(0, count($arr) - 1) )
            return true;

        return false;
    }

    public function update( $field, $key, $model )
    {
        if ( $field['type'] == 'select' || $field['type'] == 'radio' )
        {
            //Get allowed options
            $with_options = in_array($key, $model->withOptions());

            //If is not allowed to displaying all options data
            if ( $with_options !== true || ( array_key_exists('hidden', $field) && array_key_exists('removeFromForm', $field) && Admin::isAdmin() ) )
            {
                if ( ! array_key_exists('options', $field) )
                {
                    $field['options'] = [];
                } else if ( is_string($field['options']) ) {
                    $field['options'] = explode(',', $field['options']);
                }

                return $field;
            }

            //Add admin rows global scope into model
            $model->getAdminRows();

            //Get options from model, and cache them
            $options = $this->getOptionsFromBuffer('selects.'. $model->getTable() . '.options', function() use ( $model ) {
                $options = $model->getProperty('options');

                return (array)$model->getProperty('options');
            });

            /*
             * If options are defined in method od $options property
             */
            if ( array_key_exists($key, $options) || array_key_exists(($key = rtrim($key, '_id')), $options) )
            {
                $field['options'] = $options[$key];

                //If has been inserted collection between array, then convert collection into array
                if ( $field['options'] instanceof Collection )
                {
                    $field['options'] = $field['options']->toArray();
                }

            }

            /*
             * If options are defined in field
             */
            else if ( array_key_exists('options', $field) ){
                $field['options'] = is_string($field['options']) ? explode(',', $field['options']) : $field['options'];
            }

            /*
             * If options are in db as relationship
             */
            else if ( array_key_exists('belongsTo', $field) || array_key_exists('belongsToMany', $field) ) {
                $properties = explode(',', array_key_exists('belongsTo', $field) ? $field['belongsTo'] : $field['belongsToMany']);

                $rows = [];

                //When is defined column which will be in selectbox
                if ( count($properties) >= 2 && strtolower($properties[1]) != 'null' )
                {
                    //Get data from table, and bind them info buffer for better performance
                    $options = $this->getOptionsFromBuffer('selects.options.' . $properties[0], function() use ( $properties, $model ) {
                        if ($model = Admin::getModelByTable($properties[0]))
                        {
                            return $model->all()->toArray();
                        }

                        return DB::table($properties[0])->whereNull('deleted_at')->get();
                    });


                    if ( $options !== false )
                    {
                        foreach ($options as $option)
                        {
                            $option = (array)$option;

                            $key = isset($properties[2]) ? $properties[2] : 'id';

                            if ( array_key_exists('language_id', $option) )
                            {
                                $rows[ $option['language_id'] ][ $option[$key] ] = $option[$properties[1]];
                            } else {

                                //If is unknown belongs to column
                                if ( ! array_key_exists($properties[1], $option) && Admin::isAdmin() )
                                {
                                    Ajax::error('Nie je možné načítať tabuľku, keďže stĺpec <strong>'.$properties[1].'</strong> v tabuľke <strong>'.$properties[0].'</strong> neexistuje.', null, null, 500);
                                }

                                $rows[ $option[$key] ] = $option[$properties[1]];
                            }
                        }
                    }

                }

                $field['options'] = $rows;
            }

            //Checks if is non associal array
            if ( array_key_exists('options', $field) )
            {
                //Checks if is array associative
                if ( ! $this->isAssoc($field['options']) )
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