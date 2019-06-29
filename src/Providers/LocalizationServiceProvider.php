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
        $this->loadMiddlewares($kernel);

        $this->loadTranslations();
    }

    //Register localization middleware
    private function loadMiddlewares($kernel)
    {
        if ( $kernel->hasMiddleware(LocalizationMiddleware::class) )
            return;

        $kernel->prependMiddleware(LocalizationMiddleware::class);
    }

    //Load translations
    private function loadTranslations()
    {
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang/admin/', 'admin');
    }
}