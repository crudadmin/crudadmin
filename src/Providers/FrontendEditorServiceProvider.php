<?php

namespace Admin\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class FrontendEditorServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('frontendeditor', \Admin\Helpers\FrontendEditor::class);
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
        Blade::directive('uploadable', function ($expression) {
            return '<?php echo FrontendEditor::uploadable('.$expression.') ?>';
        });
    }
}
