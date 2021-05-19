<?php

namespace Admin\Providers;

use Localization;
use App\Core\Helpers\Language;
use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

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

        //Depreaced, we can remove in v4, and all webs should be migrated into Localization::prefix()
        if ( config('admin.localization_fallback', false) === true ) {
            $this->registerFallbackRouteTranslations($router);
        }
    }

    private function registerFallbackRouteTranslations(Router $router)
    {
        //Boot application routes language within prefix
        foreach (config('admin.routes', []) as $route) {
            if (! file_exists($route_path = base_or_relative_path($route))) {
                continue;
            }

            $router->group([
                'namespace' => $this->namespace,
                'prefix' => Localization::prefix(),
                'middleware' => 'web',
            ], function ($router) use ($route_path) {
                require $route_path;
            });
        }
    }
}
