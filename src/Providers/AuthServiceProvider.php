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

        config()->set('auth.guards.adminSession', [
            'driver' => 'session',
            'provider' => 'admins',
        ]);

        config()->set('auth.providers.admins', [
            'driver' => 'eloquent',
            'model' => ($authModel = Admin::getAuthModel(true)) ? $authModel : \Admin\Models\Admin::class,
        ]);
    }
}
