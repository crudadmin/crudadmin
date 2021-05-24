<?php

namespace Admin\Providers;

use Admin\Middleware\LocalizationMiddleware;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Localization;
use Route;

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

    public function boot(Kernel $kernel, Router $router)
    {
        //Boot localization. It will automatically check if can be booted,
        //and will run all features...
        Localization::fire();

        $this->loadMiddlewares($kernel, $router);

        //Added default redirect
        if ( config('admin.localization_remove_default') && Localization::canBootAutomatically() ) {
            Route::get(Localization::getDefaultLanguage()->slug, '\Admin\Controllers\LocalizationController@redirect')->middleware('web');
        }
    }

    //Register localization middleware
    private function loadMiddlewares($kernel, $router)
    {
        $router->pushMiddlewareToGroup('web', LocalizationMiddleware::class);
    }
}
