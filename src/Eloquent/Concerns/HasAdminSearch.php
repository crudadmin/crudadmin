<?php

namespace Admin\Eloquent\Concerns;

use Admin;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

trait HasAdminSearch
{
    public function scopeSearchAdminColumnText($query, $column, $term)
    {
        $query->where($this->qualifyColumn($column), 'like', '%'.$term.'%');
    }

    public function scopeSearchAdminColumnLocaleText($query, $column, $term)
    {
        $query->where(DB::raw('CONVERT(LOWER('.$this->qualifyColumn($column).') USING utf8)'), 'like', '%'.mb_strtolower($term).'%');
    }

    public function scopeSearchAdminColumnNumeric($query, $column, $from, $to)
    {
        if (! isset($from) && isset($to)) {
            $query->where($this->qualifyColumn($column), '<=', $to);
        }

        if (isset($from) && isset($to)) {
            $query->where($this->qualifyColumn($column), '>=', $from)->where($column, '<=', $to);
        }
    }

    public function scopeSearchAdminColumnDate($query, $column, $date, $dateTo, $isInterval = false)
    {
        $column = $this->qualifyColumn($column);

        if (isset($date) && ! isset($dateTo)) {
            if ( $isInterval === false ) {
                $query->whereDate($column, $date->format('Y-m-d'));
            } else {
                $query->whereDate($column, '>=', $date->format('Y-m-d'));
            }
        }

        if (! isset($date) && isset($dateTo)) {
            $query->whereDate($column, '<=', $dateTo->format('Y-m-d'));
        }

        if (isset($date) && isset($dateTo)) {
            $query->whereDate($column, '>=', $date->format('Y-m-d'))
                  ->whereDate($column, '<=', $dateTo->format('Y-m-d'));
        }

        if (! isset($date) && ! isset($dateTo)) {
            $query->whereRaw('0');
        }
    }

    /**
     * How should be multiworld sentences searched in given column.
     * Should all matches be present, or any of them?
     *
     * @param  mixed $column
     *
     * @return void
     */
    public function getSearchAdminColumnOperator($column)
    {
        return 'where'; //orWhere
    }

    public function scopeSearchAdminMultiwords($query, $search, $column, $callback, $operator = null)
    {
        $queries = array_filter(explode(' ', $search));

        $operator = $operator ?: $this->getSearchAdminColumnOperator($column);

        $query->where(function ($query) use ($queries, $callback, $operator) {
            foreach ($queries as $term) {
                $query->{$operator}(function ($query) use ($term, $callback) {
                    $callback($query, $term);
                });
            }
        });
    }

    public function scopeSearchAdminByColumn($query, $column, $columns, $search, $searchTo)
    {
        $customMethod = 'set'.Str::camel($column).'AdminSearch';
        if (method_exists($this, $customMethod)) {
            return $this->{$customMethod}($query, $search, $searchTo);
        }

        //If is imaginarry field, skip whole process
        if ( $this->isFieldType($column, ['imaginary', 'geometry']) || $this->hasFieldParam($column, ['imaginary', 'inaccessible']) ) {
            return;
        }

        //Support for encrypted fields
        else if ( $this->hasFieldParam($column, ['encrypted']) && array_key_exists($column, $this->getEncryptedFields(true)) ) {
            $query->whereJsonContains(
                '_encrypted_hashes->'.$column,
                $this->generateEncryptedHash($search)
            );
        } elseif ($searchTo) {
            $query->searchAdminColumnNumeric($column, $search, $searchTo);
        }

        //Find exact id, value
        elseif ($this->isSearchColumnPrimaryKey($column, $columns) || $this->isFieldType($column, 'checkbox')) {
            $query->where($query->qualifyColumn($column), $search);
        }

        //Find by data in relation
        elseif ($this->hasFieldParam($column, 'belongsTo')) {
            $query->searchAdminColumnRelation($column, $columns, $search, 'belongsTo');
        } elseif ($this->hasFieldParam($column, 'belongsToMany')) {
            $query->searchAdminColumnRelation($column, $columns, $search, 'belongsToMany');
        }

        //Find by fulltext in query string
        elseif ($this->hasFieldParam($column, 'locale')) {
            $query->searchAdminMultiwords($search, $column, fn($query, $term) => $query->searchAdminColumnLocaleText($column, $term));
        }

        // Basic text search
        else {
            $query->searchAdminMultiwords($search, $column, fn($query, $term) => $query->searchAdminColumnText($column, $term));
        }
    }

    private function isSearchColumnPrimaryKey($column, $columns)
    {
        if (in_array($column, ['id'])) {
            return true;
        }

        //If is correct relationship id
        if (count($columns) == 1) {
            if ($this->hasFieldParam($column, 'belongsToMany')) {
                return false;
            }

            if ($this->hasFieldParam($column, 'belongsTo')) {
                return true;
            }

            //If is select, but not multiple
            if ($this->isAdminSearchSelectColumn($column)) {
                return true;
            }
        }

        return false;
    }

    public function isAdminSearchSelectColumn($column)
    {
        return $column && $this->isFieldType($column, ['select', 'radio']) && ! $this->hasFieldParam($column, 'multiple');
    }

    public function scopeSearchAdminColumnRelation($query, $column, $columns, $search, $type = 'belongsTo')
    {
        $relation = explode(',', $this->getField($column)[$type]);

        $byColumns = $this->getSearchRelationNamesBuilder($relation, $columns);

        //We does not have columns for filter
        if ( count($byColumns) == 0 ){
            return;
        }

        $query->orWhereHas(trim_end($column, '_id'), function ($builder) use ($byColumns, $search) {
            foreach ($byColumns as $key => $selector) {
                $builder->{$key == 0 ? 'where' : 'orWhere'}(function ($builder) use ($search, $selector) {
                    $builder->searchAdminMultiwords($search, $selector, function($query, $term) use ($selector) {
                        if ($selector == 'id') {
                            $query->where($query->qualifyColumn($selector), $term);
                        } else {
                            $query->where($query->qualifyColumn($selector), 'like', '%'.$term.'%');
                        }
                    }, 'orWhere');
                });
            }
        });
    }

    /*
     * Get all columns from foreign relationships
     */
    private function getSearchRelationNamesBuilder($relation, $columns = [])
    {
        if (array_key_exists(1, $relation) && count($columns) > 1) {
            $relationModel = Admin::getModelByTable($relation[0]);

            $relationColumns = $this->getRelationshipNameBuilder($relation[1]);

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
}