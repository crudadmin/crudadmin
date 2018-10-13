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

        $this->app->bind('gogol::admin.update', \Gogol\Admin\Commands\AdminUpdateCommand::class);

        $this->app->bind('gogol::admin.migrate', \Gogol\Admin\Commands\AdminMigrationCommand::class);

        $this->app->bind('gogol::admin.model', \Gogol\Admin\Commands\AdminModelCommand::class);

        $this->app->bind('gogol::admin.button', \Gogol\Admin\Commands\AdminButtonCommand::class);

        $this->app->bind('gogol::admin.rule', \Gogol\Admin\Commands\AdminRuleCommand::class);

        $this->app->bind('gogol::admin.layout', \Gogol\Admin\Commands\AdminLayoutCommand::class);

        $this->app->bind('gogol::admin.component', \Gogol\Admin\Commands\AdminComponentCommand::class);

        $this->app->bind('gogol::admin.queue', \Gogol\Admin\Commands\EnsureQueueListenerIsRunning::class);

        $this->commands([
            'gogol::admin.install',
            'gogol::admin.update',
            'gogol::admin.migrate',
            'gogol::admin.model',
            'gogol::admin.button',
            'gogol::admin.rule',
            'gogol::admin.layout',
            'gogol::admin.component',
            'gogol::admin.queue',
        ]);
    }
}