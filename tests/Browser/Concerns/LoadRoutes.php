<?php

namespace Gogol\Admin\Tests\Browser\Concerns;

trait LoadRoutes
{
    /**
     * Load the given routes file if routes are not already cached.
     *
     * @param  string  $path
     * @return void
     */
    protected function loadRoutesFrom($app, $path)
    {
        if (! $app->routesAreCached()) {
            require $path;
        }
    }
}
?>