<?php

namespace Gogol\Admin\Tests\Browser;

use Carbon\Carbon;
use Gogol\Admin\Tests\Browser\DuskBrowser;
use Gogol\Admin\Tests\Traits\AdminTrait;
use Orchestra\Testbench\Dusk\TestCase;

class BrowserTestCase extends TestCase
{
    use AdminTrait;

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

    /**
     * Check if row exists
     * @param  string/object $model
     * @param  array  $data
     * @return void
     */
    public function assertRowExists($model, $data = [])
    {
        $model = $this->getModelClass($model);

        foreach ($data as $key => $value)
        {
            //Update checkbox values
            if ( $model->isFieldType($key, 'checkbox') )
                $data[$key] = $value == true ? 1 : 0;

            //Update filled date format into date format from db
            if ( $model->isFieldType($key, 'date') )
                $data[$key] = $value ? Carbon::createFromFormat($model->getFieldParam($key, 'date_format'), $value)->format('Y-m-d') : null;

            //Update filled datetime format into date format from db
            if ( $model->isFieldType($key, 'datetime') )
                $data[$key] = $value ? Carbon::createFromFormat($model->getFieldParam($key, 'date_format'), $value)->format('Y-m-d H:i:s') : null;

            //Update filled time format into date format from db
            if ( $model->isFieldType($key, 'time') )
                $data[$key] = $value ? Carbon::createFromFormat($model->getFieldParam($key, 'date_format'), $value)->format('H:i:s') : null;
        }

        $this->assertDatabaseHas($model->getTable(), $data);

        return $this;
    }
}

?>