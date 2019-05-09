<?php

namespace Gogol\Admin\Tests;

use Artisan;
use Gogol\Admin\Providers\AppServiceProvider;
use Gogol\Admin\Tests\App\User;
use Illuminate\Support\Facades\File;

trait AdminTrait
{
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
        //Bind app path
        $app['path'] = __DIR__.'/Stubs/app';

        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'sqlite');

        // Rewrite default user model
        $app['config']->set('auth.providers.users.model', User::class);

        // Setup default database to use sqlite :memory:
        $app['config']->set('admin.app_namespace', 'Gogol\Admin\Tests\App');

        //Reset sqlite database files
        if ( !file_exists($db_file = database_path('database.sqlite')) )
            @file_put_contents($db_file, '');

        //Boot http request before laravel app starts
        //because of bug of missing url path in request()->url()
        if ( isset($this->boot_request) && $this->boot_request === true )
            $app->handle(\Illuminate\Http\Request::capture());
    }

    /*
     * Return testing laravel app path
     */
    protected function getAppPath($path = null)
    {
        return __DIR__.'/Stubs/app'.($path ? '/'.$path : '');
    }

    /*
     * Install admin enviroment
     */
    public function installAdmin()
    {
        return $this->artisan('admin:install');
    }

    /*
     * Uninstall admin enviroment
     */
    public function unInstallAdmin()
    {
        //Remove all published resources
        foreach ($this->getAdminResources() as $path)
            $this->deleteFileOrDirectory($path);
    }
}