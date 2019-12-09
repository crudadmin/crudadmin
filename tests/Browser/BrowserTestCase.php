<?php

namespace Admin\Tests\Browser;

use Admin\Tests\Concerns\AdminIntegration;
use Admin\Tests\Concerns\DebugTravis;
use Admin\Tests\Concerns\FeatureAssertions;
use Admin\Tests\OrchestraSetup;
use Orchestra\Testbench\Dusk\TestCase;

class BrowserTestCase extends TestCase
{
    use OrchestraSetup,
        AdminIntegration,
        FeatureAssertions,
        DebugTravis;

    /**
     * Create the DuskBrowser instance.
     *
     * @param  \Facebook\WebDriver\Remote\RemoteWebDriver  $driver
     * @return \Laravel\Dusk\Browser
     */
    protected function newBrowser($driver)
    {
        return new DuskBrowser($driver);
    }

    /**
     * Setup the test environment.
     */
    protected function setUp() : void
    {
        parent::setUp();

        \Orchestra\Testbench\Dusk\Options::withoutUI();

        $this->withFactories(__DIR__.'/../Factories');

        $this->unInstallAdmin();
        $this->installAdmin();
    }

    protected function getEnvironmentSetUp($app)
    {
        $this->setAdminEnvironmentSetUp($app);

        $this->registerAllAdminModels();

        //Boot http request before laravel app starts
        //because of bug of missing url path in request()->url()
        if (! app()->runningInConsole()) {
            //set http request before laravel app starts
            //because missing url path bug in request()->url()
            $app->instance('request', \Illuminate\Http\Request::capture());
        }
    }
}
