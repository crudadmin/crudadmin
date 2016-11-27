<?php
namespace Gogol\Admin\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;

class CommandsRegisterServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        /*
         * Register commands
         */
        $this->app->bind('gogol::admin.install', function ($app) {
            return new \Gogol\Admin\Commands\AdminInstallCommand();
        });        

        $this->app->bind('gogol::admin.migrate', function ($app) {
            return new \Gogol\Admin\Commands\AdminMigrationCommand(new Filesystem);
        });

        $this->app->bind('gogol::admin.model', function ($app) {
            return new \Gogol\Admin\Commands\AdminModelCommand();
        });

        $this->commands([
            'gogol::admin.install',
            'gogol::admin.migrate',
            'gogol::admin.model',
        ]);
    }
}