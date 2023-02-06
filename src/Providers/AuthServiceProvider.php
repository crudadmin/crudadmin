<?php

namespace Admin\Providers;

use Illuminate\Support\ServiceProvider;
use Admin;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        config()->set('auth.guards.admin', [
            'driver' => 'sanctum',
            'provider' => 'admins',
            'hash' => false,
        ]);

        if ( $authModel = Admin::getAuthModel() ) {
            config()->set('auth.providers.admins', [
                'driver' => 'eloquent',
                'model' => get_class($authModel),
            ]);
        }
    }
}
