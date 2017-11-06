<?php
namespace Gogol\Admin\Providers;

use Gogol\Admin\Facades as Facades;
use Gogol\Admin\Helpers as Helpers;
use Gogol\Admin\Middleware as Middleware;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Http\Kernel;

class AppServiceProvider extends ServiceProvider
{
    protected $providers = [
        AdminServiceProvider::class,
        LocalizationServiceProvider::class,
        GettextServiceProvider::class,
        ValidatorServiceProvider::class,
        CommandsRegisterServiceProvider::class,
        PasswordResetServiceProvider::class,
        PublishServiceProvider::class,
        FieldsServiceProvider::class,
        \Intervention\Image\ImageServiceProvider::class,
    ];

    protected $facades = [
        'Admin' => Facades\Admin::class,
        'Ajax' => Helpers\Ajax::class,
        'Gettext' => Facades\Gettext::class,
        'Localization' => Facades\Localization::class,
        'Fields' => Facades\Fields::class,
        'Image' => \Intervention\Image\Facades\Image::class,
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

        /*
         * Bind route provider after application boot, for correct route actions in localizations
         */
        $this->bootProviders([
            RouteServiceProvider::class
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config_additional.php', 'admin'
        );

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

    public function bootProviders($providers = null)
    {
        foreach ($providers ?: $this->providers as $provider)
        {
            app()->register($provider);
        }
    }

    public function bootRouteMiddleware()
    {
        foreach ($this->routeMiddleware as $name => $middleware)
        {
            $router = $this->app['router'];

            /*
             * Support for laravel 5.3
             * does not know aliasMiddleware method
             */
            if ( method_exists($router, 'aliasMiddleware') )
                $router->aliasMiddleware($name, $middleware);
            else
                $router->middleware($name, $middleware);
        }
    }
}