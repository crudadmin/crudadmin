<?php

namespace Admin\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class GettextServiceProvider extends ServiceProvider
{
    public function getBladeDirective()
    {
        return '
        <script src="<?php echo Gettext::getJSPlugin(Localization::class) ?>"></script>

        <?php if ( admin() ) { ?>
        <script>window.CACSRFToken = "<?php echo csrf_token(); ?>";</script>
        <link rel="stylesheet" href="<?php echo admin_asset(\'/css/frontend.css?v=\'.Admin::getAssetsVersion()) ?>">
        <?php } ?>
        ';
    }

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
            return $this->getBladeDirective();
        });

        Blade::directive('gettext', function ($model) {
            return $this->getBladeDirective();
        });
    }
}
