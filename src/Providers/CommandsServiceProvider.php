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
        $this->commands([
            \Admin\Commands\AdminInstallCommand::class,
            \Admin\Commands\AdminButtonCommand::class,
            \Admin\Commands\AdminRequestCommand::class,
            \Admin\Commands\AdminAccountCommand::class,
            \Admin\Commands\AdminRuleCommand::class,
            \Admin\Commands\AdminLayoutCommand::class,
            \Admin\Commands\AdminSitebuilderBlockCommand::class,
            \Admin\Commands\AdminComponentCommand::class,
            \Admin\Commands\AdminPreResizeImages::class,
            \Admin\Commands\EnsureQueueListenerIsRunning::class,
            \Admin\Commands\AdminDevelopmentCommand::class,
            \Admin\Commands\AdminCleanUploadsCommand::class,
            \Admin\Commands\EncryptExistingDataCommand::class,
        ]);
    }

    public function boot()
    {
        //Register core admin model generator command mutator
        \AdminCore::registerEvents(MutateAdminModelCommand::class);
    }
}
