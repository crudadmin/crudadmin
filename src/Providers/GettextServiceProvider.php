<?php
namespace Gogol\Admin\Providers;

use Illuminate\Support\ServiceProvider;

class GettextServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['gettext'] = $this->app->share(function($app)
        {
            return new \Gogol\Admin\Helpers\Gettext();
        });
    }
}