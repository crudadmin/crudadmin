<?php

namespace Gogol\Admin\Providers;

use Illuminate\Support\ServiceProvider;

class PasswordResetServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerPasswordBroker();
    }

    /**
     * Register the password broker instance.
     *
     * @return void
     */
    protected function registerPasswordBroker()
    {

        /*
         * see Illuminate\Auth\Passwords\PasswordBrokerManager@resolve;
         */
        $this->app->config['auth.passwords.admin'] = [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60,
        ];
    }
}
