<?php
namespace Gogol\Admin\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;

class AdminServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['admin'] = $this->app->share(function($app)
        {
            return new \Gogol\Admin\Helpers\Admin(new Filesystem);
        });
    }
}