<?php

namespace Admin\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class GettextServiceProvider extends ServiceProvider
{
    private $GettextBladeDirective = '<script src="<?php echo Gettext::getJSPlugin() ?>"></script>';

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('gettext', \Admin\Helpers\Gettext::class);
    }

    public function boot()
    {
        $this->registerBladeDirectives();
    }

    /*
     * Register blade
     */
    private function registerBladeDirectives()
    {
        Blade::directive('translates', function ($model) {
            return $this->GettextBladeDirective;
        });

        Blade::directive('gettext', function ($model) {
            return $this->GettextBladeDirective;
        });
    }
}
