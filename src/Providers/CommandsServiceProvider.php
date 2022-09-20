<?php

namespace Admin\Providers;

use Illuminate\Support\ServiceProvider;
use Admin\Contracts\Commands\MutateAdminModelCommand;

class CommandsServiceProvider extends ServiceProvider
{
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

        $this->app->bind('gogol::admin.button', \Admin\Commands\AdminButtonCommand::class);

        $this->app->bind('gogol::admin.account', \Admin\Commands\AdminAccountCommand::class);

        $this->app->bind('gogol::admin.rule', \Admin\Commands\AdminRuleCommand::class);

        $this->app->bind('gogol::admin.layout', \Admin\Commands\AdminLayoutCommand::class);

        $this->app->bind('gogol::admin.sitebuilderblock', \Admin\Commands\AdminSitebuilderBlockCommand::class);

        $this->app->bind('gogol::admin.component', \Admin\Commands\AdminComponentCommand::class);

        $this->app->bind('gogol::admin.preresize', \Admin\Commands\AdminPreResizeImages::class);

        $this->app->bind('gogol::admin.queue', \Admin\Commands\EnsureQueueListenerIsRunning::class);

        $this->app->bind('gogol::admin.dev', \Admin\Commands\AdminDevelopmentCommand::class);

        $this->app->bind('gogol::admin.clean', \Admin\Commands\AdminCleanUploadsCommand::class);

        $this->app->bind('gogol::admin.encryptor', \Admin\Commands\EncryptExistingDataCommand::class);

        $this->commands([
            'gogol::admin.install',
            'gogol::admin.button',
            'gogol::admin.account',
            'gogol::admin.rule',
            'gogol::admin.layout',
            'gogol::admin.sitebuilderblock',
            'gogol::admin.component',
            'gogol::admin.preresize',
            'gogol::admin.queue',
            'gogol::admin.dev',
            'gogol::admin.clean',
            'gogol::admin.encryptor',
        ]);
    }

    public function boot()
    {
        //Register core admin model generator command mutator
        \AdminCore::registerEvents(MutateAdminModelCommand::class);
    }
}
