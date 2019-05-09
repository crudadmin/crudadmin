<?php

namespace Gogol\Admin\Tests\Browser;

use Gogol\Admin\Tests\AdminTrait;
use Orchestra\Testbench\Dusk\TestCase;

class BrowserTestCase extends TestCase
{
    use AdminTrait;

    /**
     * Setup the test environment.
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->installAdmin();
    }

    protected function getEnvironmentSetUp($app)
    {
        $this->setAdminEnvironmentSetUp($app);

        $this->registerAllAdminModels();

        //Boot http request before laravel app starts
        //because of bug of missing url path in request()->url()
        if ( ! app()->runningInConsole() )
            $app->handle(\Illuminate\Http\Request::capture());
    }
}

?>