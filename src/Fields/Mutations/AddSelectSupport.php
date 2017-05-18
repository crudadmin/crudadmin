<?php
namespace Gogol\Admin\Fields\Mutations;

use DB;
use Admin;
use Localization;
use Gogol\Admin\Helpers\Helper;

class AddSelectSupport
{
    public $attributes = ['options', 'multiple', 'default'];

    protected $maxRowsLimit = 50;

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
        if ( $field['type'] == 'select' )
        {
            //If is not allowed to displaying all options data
            if ( $model->withAllOptions() !== true || ( array_key_exists('hidden', $field) && array_key_exists('removeFromForm', $field) ) )
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
                return (array)$model->getProperty('options');
            });

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
                    //Get data from table, and bind them info buffer for better performance
                    $options = $this->getOptionsFromBuffer('selects.options.' . $properties[0], function() use ( $properties, $model ) {

                        if ($model = Admin::getModelByTable($properties[0]))
                        {
                            return $model->all()->toArray();
                        }

                        return DB::table($properties[0])->whereNull('deleted_at')->get();
                    });

                    $rows = [];

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