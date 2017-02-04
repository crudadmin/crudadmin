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
        $this->app->bind('gogol::admin.install', \Gogol\Admin\Commands\AdminInstallCommand::class);

        $this->app->bind('gogol::admin.migrate', \Gogol\Admin\Commands\AdminMigrationCommand::class);

        $this->app->bind('gogol::admin.model', \Gogol\Admin\Commands\AdminModelCommand::class);

        $this->commands([
            'gogol::admin.install',
            'gogol::admin.migrate',
            'gogol::admin.model',
        ]);
    }
}