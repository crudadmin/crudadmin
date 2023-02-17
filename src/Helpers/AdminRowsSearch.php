<?php

namespace Admin\Helpers;

use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;
use Admin;

class AdminRowsSearch
{
    public function __construct($model, $query, $search)
    {
        $this->model = $model;
        $this->query = $query;
        $this->search = $search;
    }

    /*
     * Apply multi-text search scope for given query
     */
    public function filter()
    {
        if ( !($search = $this->search) || !is_array($search) || count($search) == 0 ){
            return;
        }

        $this->query->where(function($query) use ($search) {
            foreach ($search as $item) {
                $this->applyModelFilter($query, $item);

                $deepSearchModels = ($this->model->getProperty('search') ?: [])['deep'] ?? [];

                //If specific column search is not defined, we can search in subchild models
                if ( !($item['column'] ?? null) ) {
                    foreach ($deepSearchModels as $deepItem) {
                        $classname = is_array($deepItem) ? $deepItem['model'] : $deepItem;

                        $relation = class_basename($classname);
                        $relation = is_array($deepItem) ? ($deepItem['relation'] ?? $relation) : $relation;

                        $model = new $classname;

                        $query->orWhereHas($relation, function($query) use ($item) {
                            $this->applyModelFilter($query, $item);
                        });
                    }
                }
            }
        });
    }

    private function applyModelFilter($query, $item)
    {
        $model = $query->getModel();
        $itemQuery = $item['query'] ?? null;
        $itemQueryTo = $item['query_to'] ?? null;
        $column = $item['column'] ?? null;
        $isInterval = $item['interval'] ?? false;

        $search = trim(preg_replace("/(\s+)/", ' ', str_replace('%', '', $itemQuery)));
        $searchTo = trim(preg_replace("/(\s+)/", ' ', str_replace('%', '', $itemQueryTo)));

        if ($this->isDateColumn($model, $column)) {
            $this->filterByDateColumn($query, $itemQuery, $itemQueryTo, $column, $search, $searchTo, $isInterval);
        }

        //If is more than 3 chars for searching
        elseif (strlen($search) >= 3 || ($this->isSelectColumn($model, $column) || is_numeric($search)) || $searchTo) {
            $columns = array_merge(array_keys($model->getFields()), ['id']);
            $queries = explode(' ', $search);

            //If is valid column
            if (in_array($column, $columns)) {
                $columns = [$column];
            }

            //Search scope
            $query->where(function ($builder) use ($columns, $queries, $search, $searchTo, $isInterval, $itemQuery) {
                foreach ($columns as $key => $column) {
                    //Search in all columns
                    $this->filterByColumn($builder, $columns, $column, $queries, $search, $searchTo, $isInterval, $itemQuery);
                }
            });
        }
    }

    private function isDateColumn($model, $column)
    {
        if (in_array($column, ['created_at'])) {
            return true;
        }

        return $column && $model->isFieldType($column, ['date', 'datetime', 'time']);
    }

    private function isSelectColumn($model, $column)
    {
        return $column && $model->isFieldType($column, ['select', 'radio']) && ! $model->hasFieldParam($column, 'multiple');
    }

    private function getDateFormat($model, $column, $value)
    {
        try {
            $field = $model->getField($column);

            $fromFormat = (isset($field['date_format']) ? $field['date_format'] : '') ?: 'd.m.Y';
            $fromFormat = @explode(' ', $fromFormat)[0];

            return Carbon::createFromFormat($fromFormat, $value);
        } catch (Exception $e) {
            return;
        }
    }

    private function isPrimaryKey($model, $column, $columns)
    {
        if (in_array($column, ['id'])) {
            return true;
        }

        //If is correct relationship id
        if (count($columns) == 1) {
            if ($model->hasFieldParam($column, 'belongsToMany')) {
                return false;
            }

            if ($model->hasFieldParam($column, 'belongsTo')) {
                return true;
            }

            //If is select, but not multiple
            if ($this->isSelectColumn($model, $column)) {
                return true;
            }
        }

        return false;
    }

    /*
     * Get all columns from foreign relationships
     */
    private function getNamesBuilder($model, $relation, $columns = [])
    {
        if (array_key_exists(1, $relation) && count($columns) > 1) {
            $relationModel = Admin::getModelByTable($relation[0]);

            $relationColumns = $model->getRelationshipNameBuilder($relation[1]);

            return array_values(array_filter($relationColumns, function($column) use ($relationModel) {
                if ( in_array($column, ['id', $relationModel->getKeyName()]) ) {
                    return true;
                }

                return $relationModel->getField($column) ? true : false;
            }));
        } else {
            return ['id'];
        }
    }

    private function filterByDateColumn($query, $itemQuery, $itemQueryTo, $column, $search, $searchTo, $isInterval)
    {
        $model = $query->getModel();

        if ($itemQuery) {
            $date = $this->getDateFormat($model, $column, $search);
        }

        if ($itemQueryTo) {
            $date_to = $this->getDateFormat($model, $column, $searchTo);
        }

        if (isset($date) && ! isset($date_to)) {
            if ( $isInterval === false ) {
                $query->whereDate($column, $date->format('Y-m-d'));
            } else {
                $query->whereDate($column, '>=', $date->format('Y-m-d'));
            }
        }

        if (! isset($date) && isset($date_to)) {
            $query->whereDate($column, '<=', $date_to->format('Y-m-d'));
        }

        if (isset($date) && isset($date_to)) {
            $query->whereDate($column, '>=', $date->format('Y-m-d'))
                  ->whereDate($column, '<=', $date_to->format('Y-m-d'));
        }

        if (! isset($date) && ! isset($date_to)) {
            $query->whereRaw('0');
        }
    }

    private function filterByColumn($builder, $columns, $column, $queries, $search, $searchTo, $isInterval, $itemQuery)
    {
        $model = $builder->getModel();

        $builder->orWhere(function ($builder) use ($model, $columns, $column, $queries, $search, $searchTo, $itemQuery) {
            $tableColumn = $model->fixAmbiguousColumn($column);

            //If is imaginarry field, skip whole process
            if ( $model->isFieldType($column, 'imaginary') || $model->hasFieldParam($column, ['imaginary']) ) {
                return;
            }
            //Support for encrypted fields
            else if ( $model->hasFieldParam($column, ['encrypted']) && array_key_exists($column, $model->getEncryptedFields(true)) ) {
                $builder->whereJsonContains(
                    '_encrypted_hashes->'.$column,
                    $model->generateEncryptedHash($search)
                );
            } elseif ($searchTo) {
                $builder->where(function ($builder) use ($column, $search, $searchTo, $tableColumn) {
                    if (! isset($search) && isset($searchTo)) {
                        $builder->where($tableColumn, '<=', $searchTo);
                    }

                    if (isset($search) && isset($searchTo)) {
                        $builder->where($tableColumn, '>=', $search)
                                ->where($tableColumn, '<=', $searchTo);
                    }
                });
            }

            //Find exact id, value
            elseif ($this->isPrimaryKey($model, $column, $columns)) {
                $builder->where($tableColumn, $itemQuery);
            }

            //Find by data in relation
            elseif ($model->hasFieldParam($column, 'belongsTo')) {
                $relation = explode(',', $model->getField($column)['belongsTo']);

                $byColumns = $this->getNamesBuilder($model, $relation, $columns);

                //We does not have columns for filter
                if ( count($byColumns) == 0 ){
                    return;
                }

                $builder->orWhereHas(trim_end($column, '_id'), function ($builder) use ($byColumns, $relation, $queries) {
                    foreach ($queries as $query) {
                        foreach ($byColumns as $key => $selector) {
                            if ($selector == 'id') {
                                $builder->{ $key == 0 ? 'where' : 'orWhere' }($relation[0].'.'.$selector, $query);
                            } else {
                                $builder->{ $key == 0 ? 'where' : 'orWhere' }($relation[0].'.'.$selector, 'like', '%'.$query.'%');
                            }
                        }
                    }
                });
            } elseif ($model->hasFieldParam($column, 'belongsToMany')) {
                $relation = explode(',', $model->getField($column)['belongsToMany']);

                $byColumns = $this->getNamesBuilder($model, $relation, $columns);

                //We does not have columns for filter
                if ( count($byColumns) == 0 ){
                    return;
                }

                $builder->orWhereHas(trim_end($column, '_id'), function ($builder) use ($byColumns, $relation, $queries) {
                    foreach ($queries as $query) {
                        foreach ($byColumns as $key => $selector) {
                            if ($selector == 'id') {
                                $builder->{ $key == 0 ? 'where' : 'orWhere' }($relation[0].'.'.$selector, $query);
                            } else {
                                $builder->{ $key == 0 ? 'where' : 'orWhere' }($relation[0].'.'.$selector, 'like', '%'.$query.'%');
                            }
                        }
                    }
                });
            }

            //Find by fulltext in query string
            elseif ($model->hasFieldParam($column, 'locale')) {
                //Search for all inserted words
                foreach ($queries as $key => $query) {
                    $builder->where(DB::raw('CONVERT(LOWER('.$tableColumn.') USING utf8)'), 'like', '%'.mb_strtolower($query).'%');
                }
            } else {
                //Search for all inserted words
                foreach ($queries as $key => $query) {
                    $builder->where($tableColumn, 'like', '%'.$query.'%');
                }
            }
        });
    }
}
