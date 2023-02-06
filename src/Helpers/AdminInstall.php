<?php

namespace Admin\Helpers;

class AdminInstall
{
    public static function getInstallAuthModelPath()
    {
        $modelName = config('admin.auth_eloquent');

        $laravelV7UserModelPath = app_path($modelName.'.php');
        $laravelV8UserModelPath = app_path('Models/'.$modelName.'.php');

        return file_exists($laravelV7UserModelPath) ? $laravelV7UserModelPath : $laravelV8UserModelPath;
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

        $content = str_replace('class Admin extends', 'class '.config('admin.auth_eloquent').' extends', $content);
        $content = str_replace('Admin\Models;', self::getAuthModelNamespace().';', $content);

        return @file_put_contents($userModel, $content);
    }
}