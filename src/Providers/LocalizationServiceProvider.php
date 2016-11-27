<?php
namespace Gogol\Admin\Providers;

use Illuminate\Support\ServiceProvider;

class LocalizationServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['localization'] = $this->app->share(function($app)
        {
            return new \Gogol\Admin\Helpers\Localization();
        });
    }
}