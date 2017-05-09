<?php
namespace Gogol\Admin\Providers;

use Illuminate\Support\ServiceProvider;

class FieldsServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('fields', \Gogol\Admin\Fields\Fields::class);
    }
}