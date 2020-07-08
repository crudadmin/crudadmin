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
            return '<?php echo uploadable('.$expression.') ?>';
        });

        Blade::directive('editor', function ($expression) {
            //Does not wrap into gettext, when is already gettext or variable...
            if (
                substr($expression, 0, 1) == '$'
                || (substr($expression, 0, 2) == '_(') && substr($expression, -1) == ')') {
                return '<?php echo FrontendEditor::editor('.$expression.') ?>';
            }

            return '<?php echo FrontendEditor::editor(_('.$expression.')) ?>';
        });
    }
}
