<?php

namespace Admin\Providers;

use Admin\Helpers\AutoAjax;
use Admin\Requests\Validators\UniqueJsonValidator;
use Illuminate\Support\ServiceProvider;
use Validator;

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
        $this->setAutoAjax();
        $this->loadGlobalModules();
        $this->loadValidators();
    }

    //Register admin language model
    private function loadGlobalModules()
    {
        //If user model does not exists, then load AdminUser model
        if (! \Admin::getAuthModel()) {
            \Admin::registerModel(\Admin\Models\Admin::class);
        }

        //Localization enabled
        if (\Admin::isEnabledLocalization()) {
            \Admin::registerModel(\Admin\Models\Language::class);
        }

        //Admin translations
        if (\Admin::isEnabledAdminLocalization()) {
            \Admin::registerModel(\Admin\Models\AdminLanguage::class);
        }

        //Admin groups
        if (\Admin::isRolesEnabled()) {
            \Admin::registerModel(\Admin\Models\UsersRole::class);
        }

        //Models history
        if (\Admin::isHistoryEnabled()) {
            \Admin::registerModel(\Admin\Models\ModelsHistory::class);
        }

        //Sluggable history
        if (\Admin::isSluggableHistoryEnabled()) {
            \Admin::registerModel(\Admin\Models\SluggableHistory::class);
        }

        //Seo
        if (\Admin::isSeoEnabled()) {
            \Admin::registerModel(\Admin\Models\RoutesSeo::class);
        }

        //Frontend editor
        if ( \Admin::isEnabledFrontendEditor() ) {
            \Admin::registerModel(\Admin\Models\StaticContent::class);
        }

        //Sitebuilder support
        if ( \Admin::isEnabledSitebuilder() ) {
            \Admin::registerModel(\Admin\Models\SiteBuilder::class);
        }

        //Sitetree support
        if ( \Admin::isEnabledSitetree() ) {
            \Admin::registerModel(\Admin\Models\SiteTree::class);
        }
    }

    private function loadValidators()
    {
        Validator::extend('unique_json', UniqueJsonValidator::class.'@validate', trans('validation.unique'));
    }

    private function setAutoAjax()
    {
        config()->set('autoajax.provider', AutoAjax::class);
    }
}
