<?php

namespace Gogol\Admin\Tests\Browser;

use Gogol\Admin\Providers\AppServiceProvider;
use Gogol\Admin\Tests\AdminTrait;
use Orchestra\Testbench\Dusk\TestCase;

class BrowserTestCase extends TestCase
{
    use AdminTrait;

    public function __construct()
    {
        static::useChromedriver(__DIR__.'/drivers/chromedriver75');

        parent::__construct();
    }
}

?>