<?php

namespace Admin\Tests\Browser;

use Laravel\Dusk\Browser;
use Admin\Tests\Concerns\AdminIntegration;
use Admin\Tests\Browser\Concerns\AdminBrowserAssertions;
use Admin\Tests\Browser\Concerns\AdminBrowserIntegration;

class DuskBrowser extends Browser
{
    use AdminIntegration,
        AdminBrowserIntegration,
        AdminBrowserAssertions;
}
