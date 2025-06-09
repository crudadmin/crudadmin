<?php

namespace Admin\Eloquent\Concerns;

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

    public function scopeSearchAdminColumnRelation($query, $column, $term)
    {
        if ($column == 'id') {
            $query->where($this->qualifyColumn($column), $term);
        } else {
            $query->where($this->qualifyColumn($column), 'like', '%'.$term.'%');
        }
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
}