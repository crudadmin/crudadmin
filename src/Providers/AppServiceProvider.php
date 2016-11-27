<?php
namespace Gogol\Admin\Providers;

use Gogol\Admin\Facades as Facades;
use Gogol\Admin\Helpers as Helpers;
use Gogol\Admin\Middleware as Middleware;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Http\Kernel;

class AppServiceProvider extends ServiceProvider {

    protected $providers = [
        AdminServiceProvider::class,
        RouteServiceProvider::class,
        LocalizationServiceProvider::class,
        GettextServiceProvider::class,
        ValidatorServiceProvider::class,
        CommandsRegisterServiceProvider::class,
        PasswordResetServiceProvider::class,
        PublishServiceProvider::class,
        FieldsServiceProvider::class,
    ];

    protected $facades = [
        'Admin' => Facades\Admin::class,
        'Ajax' => Helpers\Ajax::class,
        'Gettext' => Facades\Gettext::class,
        'Localization' => Facades\Localization::class,
        'Fields' => Facades\Fields::class,
    ];

    protected $routeMiddleware = [
        'admin' => Middleware\Authenticate::class,
        'admin.guest' => Middleware\RedirectIfAuthenticated::class,
    ];

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {

        /*
         * Bind variable to admin views path
         */
        $this->loadViewsFrom(__DIR__ . '/../Views', 'admin');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->bootFacades();

        $this->bootProviders();

        $this->bootRouteMiddleware();
    }

    public function bootFacades()
    {
        $this->app->booting(function()
        {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();

            foreach ($this->facades as $alias => $facade)
            {
                $loader->alias($alias, $facade);
            }

        });
    }

    public function bootProviders()
    {
        foreach ($this->providers as $provider)
        {
            app()->register($provider);
        }
    }

    public function bootRouteMiddleware()
    {
        foreach ($this->routeMiddleware as $name => $middleware)
        {
            $this->app['router']->middleware($name, $middleware);
        }
    }
}