<?php

namespace Admin\Helpers;

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

                //If specific column search is defined, we can search in subchild models
                if ( $item['column'] ?? null ) {
                    foreach ($deepSearchModels as $deepItem) {
                        $classname = is_array($deepItem) ? $deepItem['model'] : $deepItem;

                        $relation = class_basename($classname);
                        $relation = is_array($deepItem) ? ($deepItem['relation'] ?? $relation) : $relation;

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
        elseif (strlen($search) >= 3 || ($model->isAdminSearchSelectColumn($column) || is_numeric($search)) || $searchTo) {
            $columns = array_merge(array_keys($model->getFields()), ['id']);

            //If is valid column
            if (in_array($column, $columns)) {
                $columns = [$column];
            }

            //Search scope
            $query->where(function ($query) use ($columns, $search, $searchTo) {
                foreach ($columns as $key => $column) {
                    //Search in all columns
                    $query->orWhere(function ($query) use ($column, $columns, $search, $searchTo) {
                        $query->searchAdminByColumn($column, $columns, $search, $searchTo);
                    });
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

    private function getDateFormat($model, $column, $value)
    {
        try {
            return $model->newInstance()->forceFill([$column => $value])->{$column};
        } catch (Exception $e) {
            return;
        }
    }

    private function filterByDateColumn($query, $itemQuery, $itemQueryTo, $column, $search, $searchTo, $isInterval)
    {
        $model = $query->getModel();

        if ($itemQuery) {
            $date = $this->getDateFormat($model, $column, $search);
        }

        if ($itemQueryTo) {
            $dateTo = $this->getDateFormat($model, $column, $searchTo);
        }

        $query->searchAdminColumnDate($column, $date ?? null, $dateTo ?? null, $isInterval);
    }
}
