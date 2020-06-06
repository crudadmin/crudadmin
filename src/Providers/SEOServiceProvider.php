<?php

namespace Admin\Providers;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use EditorMode;

class SEOServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('seo', \Admin\Helpers\SEO::class);

        $this->registerRouteMacros();
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
        Blade::directive('seo', function ($model) {
            return "<?php SEO::setModel($model) ?>";
        });

        Blade::directive('metatags', function ($default) {
            return "<?php SEO::render($default) ?>";
        });

        /*
         * Create directives for setting an SEO property
         */
        foreach (['title', 'description', 'keywords', 'image', 'author', 'seogroup'] as $key) {
            Blade::directive($key, function ($value) use ($key) {
                return "<?php SEO::set('$key', $value) ?>";
            });
        }
    }

    /*
     * Add support for
     * Route::get('/', ...)->seo(...)
     */
    public function registerRouteMacros()
    {
        Route::macro('seo', function($param = null){
            $this->action['seo'] = $param ?: [];

            return $this;
        });

        Route::macro('visible', function($param = null){
            $url = url($this->uri);
            $controller = str_replace($this->action['namespace'], '', $this->action['controller']);
            $controller = ltrim($controller, '\\');

            EditorMode::addVisibleRoute($controller, $url);

            return $this;
        });
    }
}
