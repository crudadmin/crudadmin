<?php

namespace Gogol\Admin\Tests\Browser;

use Carbon\Carbon;
use Gogol\Admin\Tests\Browser\DuskBrowser;
use Gogol\Admin\Tests\Traits\AdminTrait;
use Orchestra\Testbench\Dusk\TestCase;
use PHPUnit\Framework\Assert as PHPUnit;

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
            if ( $model->isFieldType($key, 'checkbox') ) {
                $data[$key] = $value == true ? 1 : 0;
            }

            //Update filled date format into date format from db
            if ( $model->isFieldType($key, 'date') )
            {
                //Update multiple date field
                if ( $model->hasFieldParam($key, 'multiple') )
                {
                    foreach ($value as $k => $date)
                    {
                        $data[$key][$k] = Carbon::createFromFormat('d.m.Y', $date)->format($model->getFieldParam($key, 'date_format'));
                    }
                }

                //Update single date
                else {
                    $data[$key] = $value ? Carbon::createFromFormat($model->getFieldParam($key, 'date_format'), $value)->format('Y-m-d 00:00:00') : null;
                }
            }

            //Update filled datetime format into date format from db
            if ( $model->isFieldType($key, 'datetime') ) {
                $data[$key] = $value ? Carbon::createFromFormat($model->getFieldParam($key, 'date_format'), $value)->format('Y-m-d H:i:s') : null;
            }

            //Update filled time format into date format from db
            if ( $model->isFieldType($key, 'time') && ! $model->hasFieldParam($key, 'multiple') )
            {
                $data[$key] = $value ? Carbon::createFromFormat($model->getFieldParam($key, 'date_format'), $value)->format('H:i:s') : null;
            }
        }

        PHPUnit::assertEquals(
            $model->select(array_keys($data))->first()->toArray(), $data,
            'Table ['.$model->getTable().'] does not have excepted row'
        );

        return $this;
    }

    /*
     * Merge created row, and updated data, and get result row
     */
    public function createUpdatedRecord($row1, $row2)
    {
        foreach ($row2 as $key => $value)
        {
            if ( !is_array($row1[$key]) )
                $row1[$key] = $value;
            else {
                //If value from second array does not exists, then push it
                foreach ($value as $k => $v)
                    if ( !in_array($v, $row1[$key]) )
                        $row1[$key][] = $v;
            }
        }

        return $row1;
    }

    /*
     * Limit string and add dotts
     * We cannot use native str_limit by laravel, because
     * we do want trim empty spaces at the end of the string
     */
    public function strLimit($value, $limit, $end = '...')
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return mb_strimwidth($value, 0, $limit, '', 'UTF-8').$end;
    }
}

?>