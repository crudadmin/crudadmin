<?php

namespace Admin\Providers;

use Localization;
use App\Core\Helpers\Language;
use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function register()
    {
        $this->app->booted(function(){
            $this->mapWebRoutes($this->app->router);
        });

        $this->registerRouterMacros($this->app->router);
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    protected function mapWebRoutes(Router $router)
    {
        //Admin routes
        $router->group([
            'namespace' => 'Admin\Controllers',
            'middleware' => 'web',
        ], function ($router) {
            require __DIR__.'/../routes.php';
        });

        //Admin routes
        $router->group([
            'namespace' => 'Admin\Controllers',
            'prefix' => 'admin/api',
        ], function ($router) {
            require __DIR__.'/../Routes/api.php';
        });
    }

    public function registerRouterMacros(Router $router)
    {
        $router->macro('addLocalizationAttributes', function($localizedRouterIndex, $forcedLocale = null){
            $attributes = [
                'prefix' => Localization::prefix($forcedLocale),
                'middleware' => ['localized'],
                'localized_router_index' => $localizedRouterIndex,
            ];

            if ($this->hasGroupStack()) {
                $attributes = $this->mergeWithLastGroup($attributes);

                $this->groupStack[count($this->groupStack) - 1] = $attributes;
            } else {
                $this->groupStack[] = $attributes;
            }

            return $this;
        });
    }
}
