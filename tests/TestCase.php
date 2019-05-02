<?php

namespace Gogol\Admin\Tests;

use Artisan;
use Gogol\Admin\Providers\AppServiceProvider;
use Gogol\Admin\Tests\TestCaseTrait;
use Illuminate\Foundation\Auth\User;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use TestCaseTrait;

    /*
     * Admin user credentials
     */
    protected $credentials = [
        'email' => 'admin@admin.com',
        'password' => 'password',
    ];

    protected function getPackageProviders($app)
    {
        return [
            AppServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \IllumcreateApplicationinate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'sqlite');

        // Rewrite default user model
        $app['config']->set('auth.providers.users.model', User::class);
    }

    public function installAdmin()
    {
        return $this->artisan('admin:install');
    }
}
