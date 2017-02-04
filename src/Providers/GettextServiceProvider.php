<?php
namespace Gogol\Admin\Providers;

use Illuminate\Support\ServiceProvider;

class GettextServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('gettext', \Gogol\Admin\Helpers\Gettext::class);
    }
}