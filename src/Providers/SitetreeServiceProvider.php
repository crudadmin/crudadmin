<?php

namespace Admin\Providers;

use Admin\Contracts\AdminPage\AdminPage;
use Illuminate\Support\ServiceProvider;

class SitetreeServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if ( config('admin.sitetree', false) === false ){
            return;
        }

        $this->app->bind('sitetree', \Admin\Helpers\SiteTree\SiteTree::class);
    }
}
