<?php
namespace Gogol\Admin\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;

class FieldsServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['fields'] = $this->app->share(function($app)
        {
            return new \Gogol\Admin\Helpers\Fields\Fields();
        });
    }
}