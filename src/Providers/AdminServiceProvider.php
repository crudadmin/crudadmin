<?php

namespace Admin\Providers;

use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('admin', \Admin\Helpers\Admin::class);
    }

    public function boot()
    {
        $this->loadGlobalModules();
    }

    //Register admin language model
    private function loadGlobalModules()
    {
        //If user model does not exists, then load AdminUser model
        if (! \Admin::getModelByTable('users')) {
            \Admin::registerModel(\Admin\Models\User::class);
        }

        //Localization enabled
        if (\Admin::isEnabledLocalization()) {
            \Admin::registerModel(\Admin\Models\Language::class);
        }

        //Admin groups
        if (\Admin::isRolesEnabled()) {
            \Admin::registerModel(\Admin\Models\AdminsGroup::class);
        }

        //Models history
        if (\Admin::isHistoryEnabled()) {
            \Admin::registerModel(\Admin\Models\ModelsHistory::class);
        }

        //Sluggable history
        if (\Admin::isSluggableHistoryEnabled()) {
            \Admin::registerModel(\Admin\Models\SluggableHistory::class);
        }
    }
}
