<?php

namespace Gogol\Admin\Tests\Browser;

use Gogol\Admin\Tests\Browser\Concerns\AdminBrowserAssertions;
use Gogol\Admin\Tests\Browser\Concerns\AdminBrowserIntegration;
use Gogol\Admin\Tests\Concerns\AdminIntegration;
use Laravel\Dusk\Browser;

class DuskBrowser extends Browser
{
    use AdminIntegration,
        AdminBrowserIntegration,
        AdminBrowserAssertions;
}