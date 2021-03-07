<?php

namespace Admin\Providers;

use Admin;
use Admin\Facades as Facades;
use Admin\Helpers as Helpers;
use Admin\Middleware as Middleware;

class AppServiceProvider extends AdminHelperServiceProvider
{
    protected $providers = [
        FieldsServiceProvider::class,
        AdminServiceProvider::class,
        EventsServiceProvider::class,
        LocalizationServiceProvider::class,
        GettextServiceProvider::class,
        ValidatorServiceProvider::class,
        CommandsServiceProvider::class,
        PasswordResetServiceProvider::class,
        ImageCompressorServiceProvider::class,
        PublishServiceProvider::class,
        FrontendEditorServiceProvider::class,
        SitetreeServiceProvider::class,
        SEOServiceProvider::class,
        HashServiceProvider::class,
    ];

    protected $facades = [
        'Admin' => Facades\Admin::class,
        'Ajax' => Helpers\Ajax::class,
        'Gettext' => Facades\Gettext::class,
        'Localization' => Facades\Localization::class,
        'AdminLocalization' => Facades\AdminLocalization::class,
        'EditorMode' => Facades\EditorMode::class,
        'FrontendEditor' => Facades\FrontendEditor::class,
        'SiteTree' => Facades\SiteTree::class,
        'SEO' => Facades\SEOFacade::class,
        'ImageCompressor' => Facades\ImageCompressor::class,
        'Image' => \Intervention\Image\Facades\Image::class,
    ];

    protected $routeMiddleware = [
        'admin' => Middleware\Authenticate::class,
        'admin.guest' => Middleware\RedirectIfAuthenticated::class,
        'hasAdminRole' => Middleware\HasAdminRole::class,
        'hasDevMode' => Middleware\HasDevMode::class,
    ];

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        /*
         * Bind variable to admin views path
         */
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'admin');

        /*
         * Bind route provider after application boot, for correct route actions in localizations
         */
        $this->registerProviders([
            RouteServiceProvider::class,
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeAdminConfigs(
            require __DIR__.'/../Config/config_additional.php',
        );

        $this->registerFacades();

        $this->registerProviders(array_merge([
            config('admin.resources_provider')
        ], $this->providers));

        $this->bootRouteMiddleware();

        $this->addCrudadminStorage();
    }

    private function addCrudadminStorage()
    {
        $this->app['config']->set('filesystems.disks.crudadmin', [
            'driver' => 'local',
            'root' => public_path('uploads'),
            'url' => env('APP_URL').'/uploads',
            'visibility' => 'public',
        ]);
    }
}
