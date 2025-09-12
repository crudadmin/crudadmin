<?php

namespace Admin\Fields\Mutations;

use Admin;
use Admin\Contracts\Migrations\Types\ImaginaryType;
use Admin\Core\Contracts\DataStore;
use Admin\Core\Fields\Mutations\MutationRule;
use DB;
use Fields;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class AddSelectSupport extends MutationRule
{
    use DataStore;

    public $attributes = ['options', 'option', 'multiple', 'filterBy', 'fillBy', 'canAdd', 'canEdit', 'canView', 'canList', 'async', 'required_with_values'];

    /**
     * Checks if mutation for fields is allowed
     *
     * @param  mixed $field
     * @return void
     */
    private function isAllowedMutation($field)
    {
        return $field['type'] == 'select' || $field['type'] == 'radio';
    }

    /**
     * Check if is array associative
     *
     * @param  mixed $arr
     * @return void
     */
    private function isAssoc(array $arr)
    {
        if ([] === $arr) {
            return false;
        }

        if (array_keys($arr) !== range(0, count($arr) - 1)) {
            return true;
        }

        return false;
    }

    /**
     * Returns filter by attributes
     *
     * @param  mixed $field
     * @return void
     */
    private function getFilterBy($field)
    {
        if (array_key_exists('filterBy', $field)) {
            $filterBy = explode(',', $field['filterBy']);

            //Get relationship foreign column separator
            if (! array_key_exists(1, $filterBy)) {
                $filter_selector = last(explode('.', $filterBy[0]));

                foreach ([$filter_selector, trim_end($filter_selector, '_id').'_id'] as $key) {
                    //If field has been matched in previous fields, then get table name from belongsTo property
                    if (array_key_exists($key, $this->fields)) {
                        $table = $this->getBelongsToProperties($this->fields[$key])[0];

                        $filterBy[1] = str_singular($table).'_id';

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

    /**
     * Returns filter by attributes
     *
     * @param  mixed $fields
     * @return void
     */
    private function getFillBy($fields)
    {
        $columns = [];

        $actualKey = trim_end($this->getKey(), '_id');

        foreach ($fields as $key => $field) {
            if (array_key_exists('fillBy', $field)) {
                $fillBy = explode('.', str_replace(',', '.', $field['fillBy']));

                if (trim_end($fillBy[0], '_id') != $actualKey) {
                    break;
                }

                $columns[] = isset($fillBy[1]) ? $fillBy[1] : $key;
            }
        }

        return $columns;
    }

    /**
     * Get columns by regex prefix
     *
     * @param  mixed $properties
     * @param  mixed $field
     * @param  mixed $columns
     * @param  mixed $fields
     * @return void
     */
    private function getColumnsByProperties($properties, $field, $columns, $fields)
    {
        //Get foreign column from relationship table which will be loaded into selectbox for filterBy purposes
        if (count($filterBy = $this->getFilterBy($field)) > 0) {
            $columns[] = $filterBy[1];
        }

        //Get foreign column from relationship table which will be loaded into selectbox for fillBy purposes
        if (count($fillBy = $this->getFillBy($fields)) > 0) {
            $columns = array_merge($fillBy, $columns);
        }

        if ($model = Admin::getModelByTable($properties[0])) {
            //If relationship table has localizations
            if ( $model->isEnabledLanguageForeign() ) {
                $columns[] = 'language_id';
            }

            //We want add defaultByOption fields into column list
            //This fields must exists
            if ( array_key_exists('defaultByOption', $field) ) {
                $column = explode(',', $field['defaultByOption'])[0];

                if (
                    $model->getField($column)
                    && !(Fields::getColumnType($model, $column) instanceof ImaginaryType)
                ) {
                    $columns[] = $column;
                }
            }
        }


        return $columns;
    }

    /**
     * You can define your custom column builds in belongsTo/belongsToMany props
     * with belongsToColumns function located in parent table.
     *
        'identifier' => [
            'columns' => 'column_a,column_b',
            'render' => function($row){
                return $row->column_a.$row->column_b;
            },
        ],

        or

        'identifier' => function($row){
            return $row->column_a.$row->column_b;
        ],
     *
     *
     * @param  array  $columns
     * @param  AdminModel  $model
     *
     * return array
     */
    public function addBelongsToCustomColumnsSupport(&$columns, $model)
    {
        $customColumns = [];

        if ( method_exists($model, 'belongsToColumns') ){
            $definedColumnProps = $model->belongsToColumns();

            foreach ($columns as $key => $column) {
                if ( array_key_exists($column, $definedColumnProps) ){
                    //We need remove original column
                    unset($columns[$key]);

                    //We need register all required columns
                    if ( is_array($definedColumnProps[$column]) && $loadColumns = @$definedColumnProps[$column]['columns'] ) {
                        $customColumns[$column] = array_merge($columns, explode(',', $loadColumns));
                    } else {
                        $customColumns[$column] = ['*'];
                    }
                }
            }
        }

        return $customColumns;
    }

    /**
     * Check if column exists in array
     *
     * @param  mixed $column
     * @param  mixed $loadColumns
     * @param  mixed $option
     * @return void
     */
    private function existsColumn($column, $loadColumns, $option)
    {
        if (! $option || ! Admin::isAdmin()) {
            return;
        }

        if (count($loadColumns) == 2 && strpos($column, ':') === false && ! array_key_exists($column, $option)) {
            autoAjax()->error(
                sprintf(_('Nie je možné načítať tabuľku, keďže stĺpec <strong>%s</strong> v tabuľke <strong>%s</strong> neexistuje.'), $properties[1], $properties[0]),
                500
            )->throw();
        }
    }

    /**
     * Returns belongsTo/belongsToMany properties
     *
     * @param  mixed $field
     * @return void
     */
    private function getBelongsToProperties($field)
    {
        $attribute = array_key_exists('belongsTo', $field)
                    ? $field['belongsTo']
                    : (array_key_exists('belongsToMany', $field)
                        ? $field['belongsToMany']
                        : ''
                    );

        return explode(',', $attribute);
    }

    /**
     * Retrieve all columns which should be loaded from relationship table to fetch options list
     *
     * @param  mixed $model
     * @param  mixed $fields
     * @param  mixed $table
     * @return void
     */
    private function getAllColumnsFromAllAttributes($model, $fields, $table)
    {
        $columns = [];

        foreach ($fields as $field) {
            $properties = $this->getBelongsToProperties($field);

            if (count($properties) < 2 || $properties[0] != $table) {
                continue;
            }

            $columns = array_merge($columns, $model->getRelationshipNameBuilder($properties[1]));
        }

        return $columns;
    }

    /**
     * Renders options settings
     *
     * @param  mixed $model
     * @return void
     */
    private function getFieldSettings($model)
    {
        $settings = $model->getOptionsSettings();

        $settings = $settings[$this->getKey()] ?? $settings['*'] ?? [];

        return [
            'limit' => false,
            'displayLimit' => 10000,
            'query' => null,
            ...$settings,
        ];
    }

    /**
     * Bind options from relationship tables
     *
     * @param  mixed $model
     * @param  mixed $field
     * @param  mixed $key
     * @param  mixed $options
     * @param  mixed $fields
     * @return void
     */
    private function bindRelatedOptions($model, $field, $key, $options, $fields)
    {
        $properties = $this->getBelongsToProperties($field);

        $rows = [];

        //Override attributes from options function into property field 1
        if (array_key_exists($key, $options) && is_string($options[$key])) {
            $properties[1] = $options[$key];
        }

        //When is defined column which will be in selectbox
        if (count($properties) >= 2 && strtolower($properties[1]) != 'null') {
            $relationModel = Admin::getModelByTable($properties[0])->withAdminPermissions();

            //Get all columns from each field witch belongsTo relation
            $loadColumns = $this->getAllColumnsFromAllAttributes($model, $fields, $properties[0]);

            $loadColumns = $this->getColumnsByProperties($properties, $field, $loadColumns, $fields);

            $customColumns = $this->addBelongsToCustomColumnsSupport($loadColumns, $relationModel);

            $loadColumns = array_unique($loadColumns);

            $settings = $this->getFieldSettings($model);

            //Get data from table, and bind them info buffer for better performance
            $options = $this->cache('selects.options.'.$properties[0], function () use ($settings, $relationModel, $loadColumns, $customColumns, &$field) {
                //Check for super heave tables
                $limit = $settings['limit'] ?? 0;
                $displayLimit = $settings['displayLimit'] ?? false;
                $term = $settings['query'] ?? null;
                $ids = $settings['ids'] ?? [];

                $loadColumns[] = $relationModel->fixAmbiguousColumn('id');

                //Add all custom columns into list of loading actual columns
                $modelColumns = array_merge(Arr::flatten(array_values($customColumns)), $loadColumns);

                //All columns, or required
                $modelColumns = in_array('*', $modelColumns) ? ['*'] : $relationModel->fixAmbiguousColumn($modelColumns);

                $query = $relationModel->select($modelColumns);

                if ( $term ) {
                    $this->applyOptionsFilter($query, $term, $modelColumns);
                } else if ( count($ids) > 0 ) {
                    $query->whereIn($relationModel->fixAmbiguousColumn('id'), $ids);
                }

                // Limit options rows to display
                if ( $limit ) {
                    $query->limit($limit);
                }

                // If limit is turned off, or count is less than limit. We can show results.
                if ( $displayLimit === false || $query->count() <= $displayLimit ) {
                    return $query->get()->toArray();
                }

                // Turn on async mode.
                $field['async'] = true;

                return [];
            });

            //If is unknown belongs to column
            if (count($options) > 0) {
                $this->existsColumn($properties[1], $loadColumns, $options[0]);
            }

            if ($options !== false) {
                $this->buildOptionsRow($rows, $options, $properties, $relationModel, $loadColumns, $customColumns, $field);
            }
        }

        $field['options'] = $rows;

        return $field;
    }

    private function applyOptionsFilter($query, $term, $modelColumns)
    {
        $query->where(function($query) use ($term, $modelColumns) {
            $parts = explode(' ', $term);

            foreach ($modelColumns as $column) {
                $query->orWhere(function($query) use ($column, $parts) {
                    foreach ($parts as $part) {
                        $query->where($column, 'like', '%'.$part.'%');
                    }
                });
            }
        });
    }

    /**
     * Build each options row from relationship table to needed option properties
     *
     * @param  mixed $rows
     * @param  mixed $options
     * @param  mixed $properties
     * @param  mixed $relationModel
     * @param  mixed $loadColumns
     * @param  mixed $customColumns
     * @param  mixed $field
     * @return void
     */
    private function buildOptionsRow(&$rows, $options, $properties, $relationModel, $loadColumns, $customColumns, $field)
    {
        $key = $this->getModel()->getRelationPropertyData($field, $this->getKey())[2];

        $definedColumnProps = $relationModel && method_exists($relationModel, 'belongsToColumns')
            ? $relationModel->belongsToColumns()
            : null;

        foreach ($options as $option) {
            $option = (array) $option;

            /*
             * Build option row from given columns
             */
            foreach ($loadColumns as $column) {
                $rows[$option[$key]][$column] = $option[$column] ?? null;
            }

            /*
             * Build custom option column value from parent belongsToColumns method
             */
            foreach ($customColumns as $column => $loadCustomColumns) {
                if ( $definedColumnProps && array_key_exists($column, $definedColumnProps) ){
                    $columnProp = $definedColumnProps[$column];

                    if ( is_array($columnProp) ) {
                        $modelRow = $relationModel->forceFill($option);

                        $rows[$option[$key]][$column] = $columnProp['render']($modelRow);
                    } else if ( is_callable($columnProp) ) {
                        $rows[$option[$key]][$column] = $columnProp();
                    }
                }
            }
        }
    }

    /**
     * Helper function
     *
     * @param  mixed $options
     * @return void
     */
    private function makeOptionsFromSimpleArray($options)
    {
        $array = [];

        foreach ($options as $option) {
            $id = $option['id'];

            unset($option['id']);

            $array[$id] = $option;
        }

        return $array;
    }

    /**
     * Assoc keys options fixer
     *
     * @param  mixed $field
     * @return void
     */
    private function updateAssocField(&$field)
    {
        if (array_key_exists('options', $field)) {
            //Checks if is array associative
            if (! $this->isAssoc($field['options']) && count($field['options']) > 0) {
                //If is simple string options
                if (is_string($field['options'][0])) {
                    $field['options'] = array_combine($field['options'], $field['options']);
                }

                //If is simple array options
                elseif (is_array($field['options'][0]) && array_key_exists('id', $field['options'][0])) {
                    $field['options'] = $this->makeOptionsFromSimpleArray($field['options']);
                }
            }
        } else {
            $field['options'] = [];
        }
    }


    /**
     * Bind relationships at the end of the getFields boot method
     * for one relationships for all columns which share one table
     *
     * @param  mixed $fields
     * @param  mixed $field
     * @param  mixed $key
     * @param  mixed $model
     * @return void
     */
    public function initPostUpdate($fields, $field, $key, $model)
    {
        //Get options from model, and cache them
        $options = $this->cache('selects.'.$model->getTable().'.options', function () use ($model) {
            return (array) $model->getProperty('options', $model->getParentRow());
        });

        /*
         * If options are defined in method od $options property
         */
        if ((array_key_exists($key, $options) || array_key_exists(($key = rtrim($key, '_id')), $options)) && ! is_string($options[$key])) {
            $field['options'] = $options[$key];

            //If has been inserted collection between array, then convert collection into array
            if ($field['options'] instanceof Collection) {
                $field['options'] = $field['options']->toArray();
            }
        }

        /*
         * If options are defined in field for static multiselect
         */
        elseif (array_key_exists('options', $field)) {
            $field['options'] = is_string($field['options']) ? explode(',', $field['options']) : $field['options'];
        }

        /*
         * If options are in db as relationship
         */
        elseif (array_key_exists('belongsTo', $field) || array_key_exists('belongsToMany', $field)) {
            return $this->bindRelatedOptions($model, $field, $key, $options, $fields);
        }

        //Checks if is non associal array
        $this->updateAssocField($field);

        return $field;
    }

    /**
     * Renders options for static fields
     *
     * @param  mixed $field
     * @param  mixed $key
     * @param  mixed $model
     * @return void
     */
    private function getStaticField($field, $key, $model)
    {
        $settings = $model->getOptionsSettings();

        //Get allowed options
        $allowFieldOptions = array_key_exists('*', $settings) || array_key_exists($key, $settings);

        //If is not allowed to displaying all options data
        if ($allowFieldOptions !== true
            || (
                array_key_exists('hidden', $field)
                && array_key_exists('invisible', $field)
                && array_key_exists('removeFromForm', $field)
                && Admin::isAdmin()
            )
        ) {
            if (! array_key_exists('options', $field)) {
                $field['options'] = [];
            } elseif (is_string($field['options'])) {
                $field['options'] = explode(',', $field['options']);
            }

            return $field;
        }
    }

    /**
     * Updates field options
     *
     * @param  mixed $field
     * @param  mixed $key
     * @param  mixed $model
     * @return void
     */
    public function update($field, $key, $model)
    {
        if ($this->isAllowedMutation($field)) {
            //Update filter by property
            if (count($filterBy = $this->getFilterBy($field)) > 0) {
                $field['filterBy'] = implode(',', $filterBy);
            }

            //Return static field options, or no options
            if ($static_field = $this->getStaticField($field, $key, $model)) {
                //We need pair keys with values
                $this->updateAssocField($static_field);

                return $static_field;
            }

            /*
             * When fields will be fully loaded, then add options
             * property into array
             */
            $this->setPostUpdate(function ($fields, $field, $key, $model) {
                return $this->initPostUpdate($fields, $field, $key, $model);
            });
        }

        return $field;
    }
}
