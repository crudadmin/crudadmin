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
        $this->registerTraits();

        parent::tearDown();
    }

    /*
     * Register all traits instances
     */
    protected function registerTraits()
    {
        $uses = array_flip(class_uses_recursive(static::class));

        //Registers own event for dropping database after test
        if (isset($uses[DropDatabase::class])) {
            $this->dropDatabase();
        }

        // //Registers own event for dropping uploads data after test
        if (isset($uses[DropUploads::class])) {
            $this->dropUploads();
        }
    }

    /**
     * Setup default admin environment
     * @param  \IllumcreateApplicationinate\Foundation\Application  $app
     */
    protected function setAdminEnvironmentSetUp($app)
    {
        //Bind app path
        $app['path'] = $this->getStubPath('app');

        // Setup default database to use sqlite :memory:
        $app['config']->set('app.debug', true);
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql.database', 'crudadmin_v2_test');
        $app['config']->set('database.connections.mysql.username', 'homestead');
        $app['config']->set('database.connections.mysql.password', 'secret');

        $app['config']->set('admin.passwords', [
            '$2y$10$C6gRDQpH4suxhNbntXPsb.BCk0OKlOCncWUSwgOXgapxJnAtFd.ja' //"superpassword" in bcrypt form
        ]);

        // Rewrite default user model
        $app['config']->set('auth.providers.users.model', User::class);

        // Setup default database to use sqlite :memory:
        $app['config']->set('admin.app_namespace', 'Gogol\Admin\Tests\App');

        //Add submenu tree settings
        $app['config']->set('admin.groups', config('admin.groups', []) + [
            'level1' => 'My tree level 1',
            'level1.level2' => 'My subtree level',
            'level1.level2.level3' => 'My sub-subtree level',
        ]);

        //Allow localizations
        $app['config']->set('admin.localization', true);
        $app['config']->set('admin.gettext', true);
        $app['config']->set('admin.gettext_source_paths', array_merge(config('admin.gettext_source_paths', []), [
            $this->getStubPath('views'),
        ]));

        //Load routes
        $this->loadRoutesFrom($app, $this->getStubPath('routes.php'));
        $app['config']->set('admin.routes', [
            $this->getStubPath('routes.php')
        ]);

        //Load views
        $app['config']->set('view.paths', [
            $this->getStubPath('views')
        ]);

        //Register components path
        $app['config']->set('admin.components', [
            $this->getStubPath('components')
        ]);

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
        return $this->getStubPath('app'.($path ? '/'.$path : ''));
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

    /*
     * Check if is array associative
     */
    protected function isAssoc(array $arr)
    {
        if ([] === $arr)
            return false;

        if ( array_keys($arr) !== range(0, count($arr) - 1) )
            return true;

        return false;
    }

    /**
     * Parse select/multiselect values/keys to correct format
     * Sometimes we need just select keys, or select values
     * @param  string/object    $model
     * @param  string           $key
     * @param  mixed            $value
     * @param  boolean            $returnKey
     * @return mixed
     */
    protected function parseSelectValue($model, $key, $value, $returnKey = false)
    {
        $model = $this->getModelClass($model);

        if (
            ($model->isFieldType($key, 'select') || $model->hasFieldParam($key, ['belongsTo', 'belongsToMany']))
            && !$model->hasFieldParam($key, ['locale'], true)
        )
        {
            if ( is_array($value) && $this->isAssoc($value) )
            {
                $items = $returnKey ? array_keys($value) : array_values($value);

                $value = $model->hasFieldParam($key, ['belongsTo']) ? $items[0] : $items;
            }
        }

        return $value;
    }

    /*
     * Limit string and add dotts
     * We cannot use native str_limit by laravel, because
     * we do want trim empty spaces at the end of the string
     */
    public function strLimit($value, $limit, $end = '...')
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return mb_strimwidth($value, 0, $limit, '', 'UTF-8').$end;
    }
}