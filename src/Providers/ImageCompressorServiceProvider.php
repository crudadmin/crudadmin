<?php
namespace Admin\Providers;

use Illuminate\Support\ServiceProvider;

class ImageCompressorServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('imagecompressor', \Admin\Helpers\ImageCompressor::class);
    }
}