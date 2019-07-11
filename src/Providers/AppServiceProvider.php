<?php
namespace Admin\Providers;

use Admin\Facades as Facades;
use Admin\Helpers as Helpers;
use Admin\Middleware as Middleware;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Http\Kernel;
use Admin;

class AppServiceProvider extends ServiceProvider
{
    protected $providers = [
        AdminServiceProvider::class,
        LocalizationServiceProvider::class,
        GettextServiceProvider::class,
        ValidatorServiceProvider::class,
        CommandsServiceProvider::class,
        PasswordResetServiceProvider::class,
        ImageCompressorServiceProvider::class,
        PublishServiceProvider::class,
        FieldsServiceProvider::class,
        SEOServiceProvider::class,
        HashServiceProvider::class,
    ];

    protected $facades = [
        'Admin' => Facades\Admin::class,
        'Ajax' => Helpers\Ajax::class,
        'Gettext' => Facades\Gettext::class,
        'Localization' => Facades\Localization::class,
        'SEO' => Facades\SEOFacade::class,
        'ImageCompressor' => Facades\ImageCompressor::class,
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
         * Bind route provider after application boot, for correct route actions in localizations
         */
        $this->registerProviders([
            RouteServiceProvider::class
        ]);

        //Set admin locale
        if ( \Admin::isAdmin() === true )
        {
            app()->setLocale(config('admin.locale', 'sk'));
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerFacades();

        $this->registerProviders();

        $this->bootRouteMiddleware();
    }

    public function registerFacades()
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

    public function registerProviders($providers = null)
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