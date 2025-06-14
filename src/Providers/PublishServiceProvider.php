<?php

namespace Admin\Providers;

use Admin\Helpers\AdminInstall;
use Illuminate\Support\ServiceProvider;

class PublishServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ( app()->runningInConsole() === false ){
            return;
        }

        /*
         * Publishes
         */
        $this->publishes([__DIR__.'/../Models/Admin.php' => AdminInstall::getInstallAuthModelPath()], 'admin.user');
        $this->publishes([__DIR__.'/../Config/config.php' => config_path('admin.php')], 'admin.config');

        //Laravel 8 and lower support with resources/lang
        $this->publishes([__DIR__.'/../Resources/lang' => file_exists(base_path('lang')) ? base_path('lang') : resource_path('lang')], 'admin.languages');

        $this->publishes([__DIR__.'/../Resources/views/sitebuilder' => resource_path('views/vendor/admin/sitebuilder')], 'admin.sitebuilder');


        $this->publishes([
            __DIR__.'/../Resources/vite/.prettierrc.json' => base_path('.prettierrc.json'),
            __DIR__.'/../Resources/vite/vite.config.js' => base_path('vite.config.js'),
            __DIR__.'/../Resources/vite/package.json' => base_path('package.json'),
            __DIR__.'/../Resources/vite/scripts.blade.php' => resource_path('views/vendor/admin/slots/scripts.blade.php'),
            __DIR__.'/../Resources/vite/css/app.css' => resource_path('css/app.css'),
            __DIR__.'/../Resources/vite/js' => resource_path('js'),
        ], 'admin.vite');
    }
}
