<?php

namespace Admin\Providers;

use Admin\Middleware\LocalizationMiddleware;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class LocalizationServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('localization', \Admin\Helpers\Localization::class);
        $this->app->bind('localization.admin', \Admin\Helpers\AdminLocalization::class);
        $this->app->bind('localization.editormode', \Admin\Helpers\Localization\EditorMode::class);
    }

    public function boot(Kernel $kernel)
    {
        $this->loadMiddlewares($kernel);
    }

    //Register localization middleware
    private function loadMiddlewares($kernel)
    {
        if ($kernel->hasMiddleware(LocalizationMiddleware::class)) {
            return;
        }

        $kernel->prependMiddleware(LocalizationMiddleware::class);
    }
}
