<?php

namespace Admin\Helpers;

use Admin\Exceptions\AjaxException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Admin;
use Log;

class Ajax {

    static function success($message = null, $title = null, $data = null, $code = 200)
    {
        return self::message(
            $message ? $message : trans('admin::admin.success-save'),
            $title,
            'success',
            $data,
            $code
        );
    }

    static function error($message = null, $title = null, $data = null, $code = 200)
    {
        return self::message(
            $message ? $message : trans('admin::admin.unknown-error'),
            $title ? $title : trans('admin::admin.warning'),
            'error',
            $data,
            $code
        );
    }

    static function message($message = null, $title = null, $type = 'info', $data = null, $code = 200)
    {
        $array = [
            'type' => $type,
            'title' => $title ? $title : trans('admin::admin.info'),
            'message' => $message,
        ];

        if ( isset( $data ) )
            $array['data'] = $data;

        throw new AjaxException( response()->json($array, $code), $code );
    }

    /*
     * Push warning message into admin request errors
     */
    static function warning($message)
    {
        Admin::push('errors', $message);
    }

    static function permissionsError()
    {
        return self::error( trans('admin::admin.no-permissions'), null, null, 401 );
    }

    /*
     * Return error according to laravel debug mode
     */
    static function mysqlError(\Exception $e)
    {
        //Log error
        Log::error( $e );

        if ( env('APP_DEBUG') == true )
            Ajax::error(trans('admin::admin.migrate-error').'<br><strong>php artisan admin:migrate</strong><br><br><small>'.e($e->getMessage()).'</small>', null, null, 500);

        return Ajax::error(trans('admin::admin.db-error').'<br><br><small>'.e($e->getMessage()).'</small>', null, null, 500);
    }

}

?>