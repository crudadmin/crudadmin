<?php

namespace Gogol\Admin\Traits;

use Validator;
use Carbon\Carbon;

trait Filteriable
{
    /*
     * Filters dates by daterande date1-date2/date from query parameter
     */
    public function scopeDateRange( $query, $parameter, $column, $date_format = 'd.m.Y' )
    {
        //Date range filter
        if ( request()->has($parameter) )
        {
            $range = explode('-', request()->get($parameter));

            $error = false;

            //Validate input
            foreach ($range as $date)
            {
                $validation = Validator::make([ 'date' => $date ], [
                    'date' => 'required|date_format:'.$date_format,
                ])->fails();

                if ( $validation )
                    $error = true;
            }

            if ( ! $error )
            {
                //Check if is date range or only date for one day
                if ( count( $range ) == 2 )
                {
                    $query->whereDate($column, '>=', Carbon::createFromFormat($date_format, $range[0])->format('Y-m-d'));
                    $query->whereDate($column, '<=', Carbon::createFromFormat($date_format, $range[1])->format('Y-m-d'));
                }
                else
                {
                    $query->whereDate($column, '=', Carbon::createFromFormat($date_format, $range[0])->format('Y-m-d'));
                }

            }
        }
    }

    /**
     * Filter by get params
        Model::filter('columnname');
        Model::filter('columnname', '=>'); ...
     * @return Model
     */
    public function scopeFilter($query, $column)
    {
        $params = func_get_args();

        unset($params[0]);

        if ( request()->has($column) )
        {
            $params[] = request()->get($column);
            call_user_func_array([$query, 'where'], $params);
        }

    }

    public function scopeSearch($query, $search, $columns = null)
    {
        if ( request()->has($search) )
        {
            if ( $columns == null )
                $columns = [ $search ];

            $query->where(function($q) use( $columns, $search ) {
                foreach( $columns as $column ){
                    $q->orWhere($column, 'like', '%'.request()->get($search).'%');
                }
            });
        }
    }
}