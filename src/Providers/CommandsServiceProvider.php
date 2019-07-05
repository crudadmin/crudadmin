<?php
namespace Admin\Providers;

use Admin\Contracts\Commands\MutateAdminModelCommand;
use Illuminate\Support\ServiceProvider;

class CommandsServiceProvider extends ServiceProvider {

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
        $this->app->bind('gogol::admin.install', \Admin\Commands\AdminInstallCommand::class);

        $this->app->bind('gogol::admin.update', \Admin\Commands\AdminUpdateCommand::class);

        $this->app->bind('gogol::admin.button', \Admin\Commands\AdminButtonCommand::class);

        $this->app->bind('gogol::admin.rule', \Admin\Commands\AdminRuleCommand::class);

        $this->app->bind('gogol::admin.layout', \Admin\Commands\AdminLayoutCommand::class);

        $this->app->bind('gogol::admin.component', \Admin\Commands\AdminComponentCommand::class);

        $this->app->bind('gogol::admin.compress', \Admin\Commands\AdminCompressUploadsCommand::class);

        $this->app->bind('gogol::admin.queue', \Admin\Commands\EnsureQueueListenerIsRunning::class);


        $this->commands([
            'gogol::admin.install',
            'gogol::admin.update',
            'gogol::admin.button',
            'gogol::admin.rule',
            'gogol::admin.layout',
            'gogol::admin.component',
            'gogol::admin.compress',
            'gogol::admin.queue',
        ]);
    }

    public function boot()
    {
        //Register core admin model generator command mutator
        \AdminCore::registerEvents(MutateAdminModelCommand::class);
    }
}