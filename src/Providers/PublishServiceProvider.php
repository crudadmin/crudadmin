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
    }
}
