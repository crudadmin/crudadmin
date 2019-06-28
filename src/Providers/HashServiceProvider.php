<?php

namespace Admin\Providers;

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
            if ( class_exists('Illuminate\Hashing\HashManager') )
                return new \Admin\Hashing\HashManager($app);

            //Support for Laravel 5.4 and lower
            else
                return new \Admin\Hashing\BcryptHasher;
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
