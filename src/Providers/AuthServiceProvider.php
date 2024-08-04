<?php

namespace Admin\Providers;

use Admin;
use Admin\Helpers\PersonalAccessToken;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        config()->set('auth.providers.admins', [
            'driver' => 'eloquent',
            'model' => $modelClass = (($authModel = Admin::getAuthModel(true)) ? $authModel : \Admin\Models\Admin::class),
        ]);

        $this->app->config['auth.passwords.admin'] = [
            'provider' => (new $modelClass)->getTable(),
            'table' => config('auth.passwords.users.table', 'password_reset_tokens'),
            'expire' => 60,
        ];

        //Disable last_updated_at token updating on each request.
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
