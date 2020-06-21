<?php

namespace Admin\Tests;

use Admin\Tests\App\User;
use Illuminate\Support\Facades\File;
use Admin\Providers\AppServiceProvider as AdminServiceProvider;
use Admin\Core\Providers\AppServiceProvider as CoreServiceProvider;
use Admin\Resources\Providers\AppServiceProvider as ResourcesServiceProvider;

trait OrchestraSetup
{
    /*
     * Register all admin models into each test.
     */
    protected $loadAllAdminModels = false;

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
            CoreServiceProvider::class,
            ResourcesServiceProvider::class,
            AdminServiceProvider::class,
        ];
    }

    /**
     * Load the given routes file if routes are not already cached.
     *
     * @param  string  $path
     * @return void
     */
    protected function loadRoutesFrom($app, $path)
    {
        if (! $app->routesAreCached()) {
            require $path;
        }
    }

    /**
     * Setup default admin environment.
     * @param  \IllumcreateApplicationinate\Foundation\Application  $app
     */
    protected function setAdminEnvironmentSetUp($app)
    {
        //Bind app path, BECAUSE we does not want use
        //app directory in orchestra vendor location... We want use Stub folder
        $app->useAppPath($this->getStubPath('app'));

        $app['config']->set('app.debug', true);

        $app['config']->set('admin.passwords', [
            '$2y$10$C6gRDQpH4suxhNbntXPsb.BCk0OKlOCncWUSwgOXgapxJnAtFd.ja', //"superpassword" in bcrypt form
        ]);

        // Rewrite default user model
        $app['config']->set('auth.providers.users.model', User::class);

        $app['config']->set('admin.app_namespace', 'Admin\Tests\App');

        //Add submenu tree settings
        $app['config']->set('admin.groups', config('admin.groups', []) + [
            'fields' => 'Fields',
            'localization' => 'Localization',
            'level1' => 'My tree level 1',
            'level1.level2' => 'My subtree level',
            'level1.level2.level3' => 'My sub-subtree level',
            'single' => 'Single model',
        ]);

        //Allow history module
        $app['config']->set('admin.history', true);

        //Allow localizations
        $app['config']->set('admin.localization', true);
        $app['config']->set('admin.gettext', true);
        $app['config']->set('admin.gettext_source_paths', array_merge(config('admin.gettext_source_paths', []), [
            $this->getStubPath('views'),
        ]));

        //Load routes
        $this->loadRoutesFrom($app, $this->getStubPath('routes.php'));
        $app['config']->set('admin.routes', [
            $this->getStubPath('routes.php'),
        ]);

        //Load views
        $app['config']->set('view.paths', [
            $this->getStubPath('views'),
        ]);

        //Register components path
        $app['config']->set('admin.components', [
            $this->getStubPath('components'),
        ]);

        app()->setLocale(config('admin.locale', 'sk'));

        //Reset sqlite database files
        if (! file_exists($db_file = database_path('database.sqlite'))) {
            @file_put_contents($db_file, '');
        }

        //Register all admin models by default
        if ($this->loadAllAdminModels === true) {
            $this->registerAllAdminModels();
        }
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
        return $this->getStubPath('app'.($path ? '/'.$path : ''));
    }

    /*
     * Return stub path
     */
    public function getStubPath($path = null)
    {
        return __DIR__.'/Stubs/'.ltrim($path, '/');
    }

    /*
     * All published admin resources
     * which will be uninstalled the end of each test
     */
    protected function getPublishableResources()
    {
        return [
            $this->getBasePath().'/config/admin.php',
            $this->getBasePath().'/resources/lang/cs',
            $this->getBasePath().'/resources/lang/sk',
            $this->getBasePath().'/public/vendor/crudadmin/dist/version',
            $this->getBasePath().'/public/vendor/crudadmin/css',
            $this->getBasePath().'/public/vendor/crudadmin/js',
        ];
    }

    /*
     * All admin resources
     */
    protected function getAdminResources()
    {
        $resources = [];

        //Add publishable resources
        foreach ($this->getPublishableResources() as $item) {
            $resources[] = $item;
        }

        //Admin gettext languages
        $resources[] = $this->getBasePath().'/storage/app/lang';

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
        foreach ($this->getAdminResources() as $path) {
            $this->deleteFileOrDirectory($path);
        }
    }

    /*
     * Delete file, or whole directory
     */
    protected function deleteFileOrDirectory($path)
    {
        if (is_dir($path)) {
            File::deleteDirectory($path);
        } else {
            @unlink($path);
        }
    }

    /*
     * Register all admin models paths
     */
    public function registerAllAdminModels()
    {
        config()->set('admin.models', [
            'Admin\Tests\App' => $this->getAppPath('/*'),
        ]);
    }
}
