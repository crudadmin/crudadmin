<?php
namespace Gogol\Admin\Fields\Mutations;

use DB;
use Admin;
use Localization;
use Gogol\Admin\Fields\Mutations\MutationRule;
use Gogol\Admin\Helpers\Helper;
use Illuminate\Support\Collection;
use Ajax;

class AddSelectSupport extends MutationRule
{
    public $attributes = ['options', 'multiple', 'default', 'filterBy'];

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

    private function getFilterBy($field)
    {
        if ( array_key_exists('filterBy', $field) )
        {
            $filterBy = explode(',', $field['filterBy']);

            //Get relationship foreign column separator
            if ( ! array_key_exists(1, $filterBy) ){
                foreach ([$filterBy[0], $filterBy[0] . '_id'] as $key) {
                    //If field has been matched in previous fields, then get table name from belongsTo property
                    if ( array_key_exists($key, $this->fields) ){
                        $table = $this->getBelongsToProperties($this->fields[$key])[0];

                        $filterBy[1] = str_singular($table) . '_id';

                        break;
                    } else {
                        $filterBy[1] = $key;
                    }
                }
            }

            return $filterBy;
        }

        return [];
    }

    /*
     * Get columns by regex prefix
     */
    private function getColumnsByProperties($properties, $field, $columns = [])
    {
        //Get foreign column from relationship table which will be loaded into selectbox
        if ( count($filterBy = $this->getFilterBy($field)) > 0 )
            $columns[] = $filterBy[1];

        //If relationship table has localizations
        if (($model = Admin::getModelByTable($properties[0])) && $model->isEnabledLanguageForeign())
            $columns[] = 'language_id';

        return $columns;
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

    private function getBelongsToProperties($field)
    {
        return explode(',', array_key_exists('belongsTo', $field) ? $field['belongsTo'] : $field['belongsToMany']);
    }

    public function update( $field, $key, $model )
    {
        if ( $field['type'] == 'select' || $field['type'] == 'radio' )
        {
            //Update filter by property
            if ( count($filterBy = $this->getFilterBy($field)) > 0 )
                $field['filterBy'] = implode(',', $filterBy);

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
                $properties = $this->getBelongsToProperties($field);

                $rows = [];

                //Override options from function into property field 1
                if ( array_key_exists($key, $options) && is_string($options[$key]) )
                    $properties[1] = $options[$key];

                //When is defined column which will be in selectbox
                if ( count($properties) >= 2 && strtolower($properties[1]) != 'null' )
                {
                    $load_columns = $this->getColumnsByProperties($properties, $field, $model->getRelationshipNameBuilder($properties[1]));

                    //Get data from table, and bind them info buffer for better performance
                    $options = $this->getOptionsFromBuffer('selects.options.' . $properties[0], function() use ( $properties, $model, $load_columns ) {

                        $load_columns[] = 'id';

                        if ($model = Admin::getModelByTable($properties[0]))
                            return $model->select($load_columns)->get()->toArray();

                        return DB::table($properties[0])->select($load_columns)->whereNull('deleted_at')->get();
                    });

                    //If is unknown belongs to column
                    if ( count($options) > 0 )
                        $this->existsColumn($properties[1], $load_columns, $options[0]);

                    if ( $options !== false )
                    {
                        $key = isset($properties[2]) ? $properties[2] : 'id';

                        foreach ($options as $option)
                        {
                            $option = (array)$option;

                            foreach ($load_columns as $column) {
                                $rows[ $option[$key] ][$column] = $option[$column];
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