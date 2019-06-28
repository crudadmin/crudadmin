<?php

namespace Admin\Tests\Browser;

use Admin\Tests\Browser\Concerns\AdminBrowserAssertions;
use Admin\Tests\Browser\Concerns\AdminBrowserIntegration;
use Admin\Tests\Concerns\AdminIntegration;
use Laravel\Dusk\Browser;

class DuskBrowser extends Browser
{
    use AdminIntegration,
        AdminBrowserIntegration,
        AdminBrowserAssertions;
}