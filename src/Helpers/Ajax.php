<?php

namespace Admin\Helpers;

use Log;
use Admin;

/**
 * THIS IS ONLY LEGACY CLASS
 * for crudadmin 3 and lower.
 */
class Ajax
{
    public static function success($message = null, $title = null, $data = null, $code = 200)
    {
        autoAjax()->title($title)->message($message ?: trans('admin::admin.success-save'))->type('success')->data($data)->code($code)->throw();
    }

    public static function error($message = null, $title = null, $data = null, $code = 200)
    {
        autoAjax()->title($title)->message($message)->type('error')->data($data)->code($code)->throw();
    }

    public static function message($message = null, $title = null, $type = 'info', $data = null, $code = 200)
    {
        autoAjax()->title($title)->type($type)->message($message)->data($data)->code($code)->throw();
    }

    /**
     * Push warning message into admin request error
     *
     * @param  string  $message
     * @param  string  $type (notice|error)
     */
    public static function pushMessage($message, $type = 'notice')
    {
        autoAjax()->pushMessage($message, $type);
    }

    /*
     * Push warning message into admin request error
     */
    public static function warning($message)
    {
        self::pushMessage($message, 'error');
    }

    /*
     * Push notice
     */
    public static function notice($message)
    {
        self::pushMessage($message);
    }

    /*
     * Permission dannied helper
     */
    public static function permissionsError()
    {
        return autoAjax()->permissionsError()->throw();
    }

    /*
     * Return error according to laravel debug mode
     */
    public static function mysqlError(\Exception $e)
    {
        return autoAjax()->mysqlError($e)->throw();
    }
}
