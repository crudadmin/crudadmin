<?php
namespace Gogol\Admin\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class SEOServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('seo', \Gogol\Admin\Helpers\SEO::class);
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
        foreach (['title', 'description', 'keywords', 'image', 'author'] as $key)
        {
            Blade::directive($key, function ($value) use ($key) {
                return "<?php SEO::set('$key', $value) ?>";
            });
        }
    }
}