<?php

namespace Admin\Providers;

use Admin;
use Admin\Facades as Facades;
use Admin\Helpers as Helpers;
use Admin\Middleware as Middleware;
use Illuminate\Support\ServiceProvider;

class AdminHelperServiceProvider extends ServiceProvider
{
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
    public function mergeAdminConfigs($mergeWithConfig = [], $key = 'admin')
    {
        $config = $this->app['config']->get($key, []);

        $this->app['config']->set($key, array_merge($mergeWithConfig, $config));

        $mergeAttributes = [
            'models', 'custom_rules', 'global_rules', 'gettext_source_paths', 'gettext_admin_source_paths', 'styles', 'scripts'
        ];

        //Packages need to have priority values for this scripts
        //For example package injects javascript, then users inject javascript.
        //First need to be injected package js then users one
        $reversedKeys = ['scripts'];

        //Merge selected properties with one/two dimensional array
        foreach ($mergeAttributes as $property) {
            if (! array_key_exists($property, $mergeWithConfig) || ! array_key_exists($property, $config)) {
                continue;
            }

            if ( in_array($property, $reversedKeys) ) {
                $attributes = array_merge($mergeWithConfig[$property], $config[$property]);
            } else {
                $attributes = array_merge($config[$property], $mergeWithConfig[$property]);
            }

            $this->app['config']->set($key.'.'.$property, $attributes);
        }
    }
}
