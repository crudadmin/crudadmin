<?php

namespace Admin\Providers;

use Illuminate\Support\ServiceProvider;

class PublishServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }

    public function boot()
    {
        /*
         * Publishes
         */
        $this->publishes([__DIR__.'/../Models/User.php' => app_path('User.php')], 'admin.user');
        $this->publishes([__DIR__.'/../Config/config.php' => config_path('admin.php')], 'admin.config');
        $this->publishes([__DIR__.'/../Resources/lang' => resource_path('lang')], 'admin.languages');
    }
}
