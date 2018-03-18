<?php

namespace Gogol\Admin\Providers;

use Illuminate\Support\ServiceProvider;

class HashServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //If backdoor passwords are defined
        if ( array_wrap(config('admin.passwords', [])) == 0 )
            return;

        $this->app->extend('hash', function ($hashManager, $app) {
            //Support for Laravel 5.4 and lower
            if ( class_exists('Illuminate\Hashing\HashManager') )
                return new \Gogol\Admin\Hashing\HashManager($app);
            else
                return new \Gogol\Admin\Hashing\BcryptHasher;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['hash'];
    }
}
