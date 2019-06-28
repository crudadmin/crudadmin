<?php
namespace Admin\Providers;

use Admin\Middleware\LocalizationMiddleware;
use Illuminate\Contracts\Http\Kernel;
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

    public function boot(Kernel $kernel)
    {
        //Register localization middleware
        if ( ! $kernel->hasMiddleware(LocalizationMiddleware::class) )
            $kernel->prependMiddleware(LocalizationMiddleware::class);

        //Load translations
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang/admin/', 'admin');
    }
}