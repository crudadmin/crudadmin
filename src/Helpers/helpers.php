<?php

if ( ! function_exists('admin_asset') )
{
    function admin_asset($path, $root = false)
    {
        if (substr($path, 0, 7) == 'http://' || substr($path, 0, 8) == 'https://')
            return $path;

        return asset(($root == false ? Admin::getAdminAssetsPath() : '') . '/' . trim($path, '/'));
    }
}

if ( ! function_exists('admin_action') )
{
    function admin_action($controller, $params = null)
    {
        return action('\Gogol\Admin\Controllers\\'.$controller, $params);
    }
}

if ( ! function_exists('isActiveController') )
{
    function isActiveController($controller, $text = null)
    {
        return \Gogol\Admin\Helpers\Helper::isActive($controller, $text);
    }
}

if ( ! function_exists('admin') ) {
    function admin()
    {
        $guard = auth()->guard('web');

        //Check if is student logged
        if( ! $guard->check() )
            return false;

        return $guard->user();
    }
}

if ( ! function_exists('trim_end') ) {
    function trim_end($string, $trim)
    {
        while (substr($string, -strlen($trim)) == $trim) {
            $string = substr($string, 0, -strlen($trim));
        }

        return $string;
    }
}


/*
 * Returns base or relative path
 */
function base_or_relative_path($path)
{
    $path = $path[0] == '/' ? $path : base_path($path);

    return trim_end($path, '/');
}
?>