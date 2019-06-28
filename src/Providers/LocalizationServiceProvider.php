<?php
namespace Admin\Providers;

use Illuminate\Support\ServiceProvider;

class LocalizationServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('localization', \Admin\Helpers\Localization::class);
    }

    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang/admin/', 'admin');
    }
}