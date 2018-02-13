<?php

if ( ! function_exists('admin_asset') )
{
    function admin_asset($path)
    {
        if (substr($path, 0, 7) == 'http://' || substr($path, 0, 8) == 'https://')
            return $path;

        return asset(Admin::getAdminAssetsPath(). '/' . trim($path, '/'));
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
?>