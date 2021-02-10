<?php

namespace Admin\Helpers;

class AdminInstall
{
    public static function getInstallAuthModelPath()
    {
        $laravelV7UserModelPath = app_path('User.php');
        $laravelV8UserModelPath = app_path('Models/User.php');

        return file_exists($laravelV8UserModelPath) ? $laravelV8UserModelPath : $laravelV7UserModelPath;
    }

    public static function getAuthModelNamespace()
    {
        $rootNamespace = config('admin.app_namespace');

        $installModelPath = self::getInstallAuthModelPath();
        $installModelPath = dirname(str_replace(app_path(), '', $installModelPath));
        $installModelPath = str_replace('/', '\\', $installModelPath);

        return trim_end($rootNamespace.$installModelPath, "\\");
    }

    public static function setAuthModelNamespace()
    {
        //Replace namespace in new user model
        $userModel = self::getInstallAuthModelPath();

        if ( !($content = @file_get_contents($userModel)) ){
            return false;
        }

        $content = str_replace('Admin\Models;', self::getAuthModelNamespace().';', $content);

        return @file_put_contents($userModel, $content);
    }
}