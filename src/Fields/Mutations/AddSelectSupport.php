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

    /*
     * Get columns by regex prefix
     */
    private function getColumnsByProperties($properties, $columns = ['id'])
    {
        preg_match_all('/(?<!\\\\)[\:^]([0-9,a-z,A-Z$_]+)+/', $properties[1], $matches);

        if ( count($matches[1]) == 0 )
            $columns[] = $properties[1];
        else
            $columns = array_merge($matches[1], $columns);

        return $columns;
    }

    /*
     * Build options value
     */
    private function makeValueByProperty($row, $value, $load_columns)
    {
        //If is symple one column
        if ( in_array($value, $load_columns) )
            return $row[$value];

        //If is dynamic columns
        foreach ($load_columns as $column ) {
            $value = str_replace(':'.$column, $row[$column], $value);
        }

        return str_replace('\:', ':', $value);
    }

    /*
     * Check if column exists in array
     */
    private function existsColumn($column, $load_columns, $option)
    {
        if ( !$option || !Admin::isAdmin() )
            return;

        if ( count($load_columns) == 2 && strpos($column, ':') === false && !array_key_exists($column, $option) )
        {
            Ajax::error('Nie je možné načítať tabuľku, keďže stĺpec <strong>'.$properties[1].'</strong> v tabuľke <strong>'.$properties[0].'</strong> neexistuje.', null, null, 500);
        }
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
            if ( (array_key_exists($key, $options) || array_key_exists(($key = rtrim($key, '_id')), $options)) && !is_string($options[$key]) )
            {
                $field['options'] = $options[$key];

                //If has been inserted collection between array, then convert collection into array
                if ( $field['options'] instanceof Collection )
                {
                    $field['options'] = $field['options']->toArray();
                }

            }

            /*
             * If options are defined in field for static multiselect
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

                //Override options from function into property field 1
                if ( array_key_exists($key, $options) && is_string($options[$key]) )
                    $properties[1] = $options[$key];

                //When is defined column which will be in selectbox
                if ( count($properties) >= 2 && strtolower($properties[1]) != 'null' )
                {
                    $load_columns = $this->getColumnsByProperties($properties);

                    //Get data from table, and bind them info buffer for better performance
                    $options = $this->getOptionsFromBuffer('selects.options.' . $properties[0], function() use ( $properties, $model, $load_columns ) {
                        if ($model = Admin::getModelByTable($properties[0]))
                            return $model->select($load_columns)->get()->toArray();

                        return DB::table($properties[0])->select($load_columns)->whereNull('deleted_at')->get();
                    });

                    //If is unknown belongs to column
                    if ( count($options) > 0 )
                        $this->existsColumn($properties[1], $load_columns, $options[0]);

                    if ( $options !== false )
                    {
                        foreach ($options as $option)
                        {
                            $option = (array)$option;

                            $key = isset($properties[2]) ? $properties[2] : 'id';

                            if ( array_key_exists('language_id', $option) )
                            {
                                $rows[ $option['language_id'] ][ $option[$key] ] = $this->makeValueByProperty($option, $properties[1], $load_columns);
                            } else {
                                $rows[ $option[$key] ] = $this->makeValueByProperty($option, $properties[1], $load_columns);
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