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

    protected $admin_namespace = 'Admin\Controllers';

    /**
     * Multi languages localization support.
     * @var string/boolean
     */
    protected $localization = false;

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function boot()
    {
        //Boot web multi languages support
        if ( Localization::canBootAutomatically() ) {
            $this->localization = Localization::boot();
        }

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function map(Router $router)
    {
        $this->mapWebRoutes($router);

        //
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
            'namespace' => $this->admin_namespace,
            'middleware' => 'web',
        ], function ($router) {
            require __DIR__.'/../routes.php';
        });

        //Boot application routes language within prefix
        foreach (config('admin.routes', []) as $route) {
            if (! file_exists($route_path = base_or_relative_path($route))) {
                continue;
            }

            $router->group([
                'namespace' => $this->namespace,
                'prefix' => $this->localization,
                'middleware' => 'web',
            ], function ($router) use ($route_path) {
                require $route_path;
            });
        }
    }
}
