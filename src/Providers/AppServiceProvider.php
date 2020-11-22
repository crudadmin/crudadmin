<?php

namespace Admin\Providers;

use Admin;
use Admin\Facades as Facades;
use Admin\Helpers as Helpers;
use Admin\Middleware as Middleware;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $providers = [
        FieldsServiceProvider::class,
        AdminServiceProvider::class,
        EventsServiceProvider::class,
        LocalizationServiceProvider::class,
        GettextServiceProvider::class,
        ValidatorServiceProvider::class,
        CommandsServiceProvider::class,
        PasswordResetServiceProvider::class,
        ImageCompressorServiceProvider::class,
        PublishServiceProvider::class,
        FrontendEditorServiceProvider::class,
        SEOServiceProvider::class,
        HashServiceProvider::class,
    ];

    protected $facades = [
        'Admin' => Facades\Admin::class,
        'Ajax' => Helpers\Ajax::class,
        'Gettext' => Facades\Gettext::class,
        'Localization' => Facades\Localization::class,
        'AdminLocalization' => Facades\AdminLocalization::class,
        'EditorMode' => Facades\EditorMode::class,
        'FrontendEditor' => Facades\FrontendEditor::class,
        'SEO' => Facades\SEOFacade::class,
        'ImageCompressor' => Facades\ImageCompressor::class,
        'Image' => \Intervention\Image\Facades\Image::class,
    ];

    protected $routeMiddleware = [
        'admin' => Middleware\Authenticate::class,
        'admin.guest' => Middleware\RedirectIfAuthenticated::class,
        'hasAdminRole' => Middleware\HasAdminRole::class,
        'hasDevMode' => Middleware\HasDevMode::class,
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
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'admin');

        /*
         * Bind route provider after application boot, for correct route actions in localizations
         */
        $this->registerProviders([
            RouteServiceProvider::class,
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeAdminConfigs();

        $this->registerFacades();

        $this->registerProviders(array_merge([
            config('admin.resources_provider')
        ], $this->providers));

        $this->bootRouteMiddleware();
    }

    public function registerFacades()
    {
        $this->app->booting(function () {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();

            foreach ($this->facades as $alias => $facade) {
                $loader->alias($alias, $facade);
            }
        });
    }

    public function registerProviders(array $providers)
    {
        foreach ($providers as $provider) {
            app()->register($provider);
        }
    }

    public function bootRouteMiddleware()
    {
        foreach ($this->routeMiddleware as $name => $middleware) {
            $router = $this->app['router'];

            /*
             * Support for laravel 5.3
             * does not know aliasMiddleware method
             */
            if (method_exists($router, 'aliasMiddleware')) {
                $router->aliasMiddleware($name, $middleware);
            } else {
                $router->middleware($name, $middleware);
            }
        }
    }

    /*
     * Merge crudadmin config with esolutions config
     */
    private function mergeAdminConfigs($key = 'admin')
    {
        //Additional CrudAdmin Config
        $crudAdminConfig = require __DIR__.'/../Config/config_additional.php';

        $config = $this->app['config']->get($key, []);

        $this->app['config']->set($key, array_merge($crudAdminConfig, $config));

        //Merge selected properties with one/two dimensional array
        foreach (['models', 'custom_rules', 'global_rules', 'gettext_source_paths', 'gettext_admin_source_paths'] as $property) {
            if (! array_key_exists($property, $crudAdminConfig) || ! array_key_exists($property, $config)) {
                continue;
            }

            $attributes = array_merge($config[$property], $crudAdminConfig[$property]);

            $this->app['config']->set($key.'.'.$property, $attributes);
        }
    }
}
