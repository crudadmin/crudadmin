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
        $this->publishes([__DIR__.'/../Models/User.php' => AdminInstall::getInstallAuthModelPath()], 'admin.user');
        $this->publishes([__DIR__.'/../Config/config.php' => config_path('admin.php')], 'admin.config');
        $this->publishes([__DIR__.'/../Resources/lang' => resource_path('lang')], 'admin.languages');
        $this->publishes([__DIR__.'/../Resources/views/sitebuilder' => resource_path('views/vendor/admin/sitebuilder')], 'admin.sitebuilder');
    }
}
