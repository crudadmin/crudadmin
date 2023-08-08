<?php

namespace Admin\Providers;

use Admin;
use Admin\Facades as Facades;
use Admin\Helpers as Helpers;
use Admin\Middleware as Middleware;
use Illuminate\Support\ServiceProvider;
use Arr;

class AdminHelperServiceProvider extends ServiceProvider
{
    public function registerFacades(array $facades = null)
    {
        $this->app->booting(function () use ($facades) {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();

            foreach ($facades ?: $this->facades as $alias => $facade) {
                $isArray = is_array($facade);

                $loader->alias(
                    $alias,
                    $isArray ? $facade['facade'] : $facade
                );

                if ( $isArray ){
                    $this->app->bind($facade['class'][0], $facade['class'][1]);
                }
            }
        });
    }

    public function registerProviders(array $providers = null)
    {
        foreach ($providers ?: $this->providers as $provider) {
            app()->register($provider);
        }
    }

    public function bootRouteMiddleware(array $routeMiddlewares = null)
    {
        foreach ($routeMiddlewares ?: $this->routeMiddleware as $name => $middleware) {
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
    public function mergeAdminConfigs($newConfig = [], $key = 'admin')
    {
        //We need prepare admin groups, which may be closure. But we need cast it into array, which will me berged later
        //when translations are ready
        if ( isset($newConfig['groups']) ){
            $newConfig['groups'] = array_wrap($newConfig['groups']);
        }

        $this->mergeConfigs(
            $newConfig,
            $key,
            [],
            //Packages need to have priority values for this scripts
            //For example package injects javascript, then users inject javascript.
            //First need to be injected package js then users one
            ['scripts']
        );

        if ( class_exists(Admin::class) ){
            Admin::cacheConfig();
        }
    }

    private function isAssocArray(array $arr)
    {
        if ([] === $arr)  {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }


    /**
     * Merge crudadmin config with admineshop config
     *
     * @param  array  $newConfig
     * @param  string  $key
     * @param  array  $mergeKeys - we can merge recursive keys
     * @param  array  $reversedKeys - some keys needs to be in reversed order
     * @param  bool  $override
     */
    public function mergeConfigs($newConfig, $key, $mergeKeys = [], $reversedKeys = [], $override = false)
    {
        $config = $this->app['config']->get($key, []);

        $mergeKeys = array_merge(array_keys($newConfig), $mergeKeys);

        //Merge selected properties with one/two dimensional array
        foreach ($mergeKeys as $property) {
            $newValue = Arr::get($newConfig, $property);
            $oldValue = Arr::get($config, $property);

            if ( is_array($newValue) && is_array($oldValue) ) {
                if (
                    in_array($property, $reversedKeys)
                    || $this->isAssocArray($newValue) && $this->isAssocArray($oldValue)
                ) {
                    $attributes = array_merge($newValue, $oldValue);
                } else {
                    $attributes = array_merge($oldValue, $newValue);
                }
            } else {
                $attributes = $override === false && Arr::has($config, $property) ? $oldValue : $newValue;
            }

            $this->app['config']->set($key.'.'.$property, $attributes);
        }
    }

    /*
     * Update markdown settings
     */
    public function mergeMarkdownConfigs($key = 'mail.markdown')
    {
        $config = $this->app['config']->get($key, []);

        //Add themes from admineshop
        $config['paths'] = array_merge($config['paths'], [
            __DIR__ . '/../Views/mail/',
        ]);

        $this->app['config']->set($key, $config);
    }

    /*
     * Add full components path
     */
    public function pushComponentsPaths($key = 'admin.components')
    {
        $config = $this->app['config']->get($key, []);

        //Add themes from admineshop
        $config = array_merge($config, [
            __DIR__ . '/../Admin/Components',
        ]);

        $this->app['config']->set($key, $config);
    }

    /*
     * For logged administrator turn of eshop/web cache
     */
    public function turnOfCacheForAdmin()
    {
        if ( admin() ) {
            view()->composer('*', function ($view) {
                $this->app['config']->set('admin.cache_time', 1);
            });
        }
    }
}
