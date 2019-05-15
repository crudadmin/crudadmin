<?php

namespace Gogol\Admin\Tests\Traits;

use Artisan;
use Gogol\Admin\Providers\AppServiceProvider;
use Gogol\Admin\Tests\App\User;
use Gogol\Admin\Tests\Traits\DropDatabase;
use Gogol\Admin\Tests\Traits\DropUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

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
     * Setup the test environment.
     */
    protected function tearDown() : void
    {
        $uses = array_flip(class_uses_recursive(static::class));

        //Registers own event for dropping database after test
        if (isset($uses[DropDatabase::class])) {
            $this->dropDatabase();
        }

        //Registers own event for dropping uploads data after test
        if (isset($uses[DropUploads::class])) {
            $this->dropUploads();
        }

        parent::tearDown();
    }

    /**
     * Setup default admin environment
     * @param  \IllumcreateApplicationinate\Foundation\Application  $app
     */
    protected function setAdminEnvironmentSetUp($app)
    {
        //Bind app path
        $app['path'] = $this->getStubpath('app');

        // Setup default database to use sqlite :memory:
        $app['config']->set('app.debug', true);
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql.database', 'crudadmin_v2_test');
        $app['config']->set('database.connections.mysql.username', 'homestead');
        $app['config']->set('database.connections.mysql.password', 'secret');


        // Rewrite default user model
        $app['config']->set('auth.providers.users.model', User::class);

        // Setup default database to use sqlite :memory:
        $app['config']->set('admin.app_namespace', 'Gogol\Admin\Tests\App');

        app()->setLocale(config('admin.locale', 'sk'));

        //Reset sqlite database files
        if ( !file_exists($db_file = database_path('database.sqlite')) )
            @file_put_contents($db_file, '');
    }

    /**
     * Define environment setup.
     *
     * @param  \IllumcreateApplicationinate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->setAdminEnvironmentSetUp($app);
    }

    /*
     * Return testing laravel app path
     */
    protected function getAppPath($path = null)
    {
        return $this->getStubpath('app'.($path ? '/'.$path : ''));
    }

    /*
     * Return stub path
     */
    public function getStubPath($path = null)
    {
        return __DIR__.'/../Stubs/'.ltrim($path, '/');
    }

    /*
     * All published admin resources
     */
    protected function getPublishableResources()
    {
        return [
            config_path('admin.php'),
            resource_path('lang/cs'),
            resource_path('lang/sk'),
            public_path('vendor/crudadmin/dist/version'),
            public_path('vendor/crudadmin/css'),
        ];
    }

    /*
     * All admin resources
     */
    protected function getAdminResources()
    {
        $resources = [];

        //Add publishable resources
        foreach ($this->getPublishableResources() as $item)
            $resources[] = $item;

        return $resources;
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

    /*
     * Register all admin models paths
     */
    public function registerAllAdminModels()
    {
        config()->set('admin.models', [
            'Gogol\Admin\Tests\App\Models' => $this->getAppPath('Models')
        ]);
    }

    /*
     * Delete file, or whole directory
     */
    protected function deleteFileOrDirectory($path)
    {
        if ( is_dir($path) )
            File::deleteDirectory($path);
        else
            @unlink($path);
    }


    /**
     * Return object of class
     * @param  string/object $model
     * @return object
     */
    private function getModelClass($model)
    {
        return is_object($model) ? $model : new $model;
    }

}