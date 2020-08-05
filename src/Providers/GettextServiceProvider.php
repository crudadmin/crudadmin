<?php

namespace Admin\Providers;

use Gettext\Extractors\VueJs;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class GettextServiceProvider extends ServiceProvider
{
    public function getBladeDirective()
    {
        return file_get_contents(view('admin::directives.gettext-setup')->getPath());
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

        //Fix vuejs v-html extractor
        VueJs::$options = VueJs::$options + [
            'attributePrefixes' => [
                ':',
                'v-bind:',
                'v-on:',
                'v-text',
                'v-html',
            ],
        ];
    }
}
