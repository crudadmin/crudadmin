<?php

namespace Gogol\Admin\Tests\Browser;

use Carbon\Carbon;
use Gogol\Admin\Tests\Browser\Concerns\LoadRoutes;
use Gogol\Admin\Tests\Browser\DuskBrowser;
use Gogol\Admin\Tests\Traits\AdminTrait;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Dusk\TestCase;
use PHPUnit\Framework\Assert as PHPUnit;

class BrowserTestCase extends TestCase
{
    use AdminTrait,
        LoadRoutes;

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

        $this->installAdmin();
    }

    protected function getEnvironmentSetUp($app)
    {
        $this->setAdminEnvironmentSetUp($app);

        $this->registerAllAdminModels();

        //Boot http request before laravel app starts
        //because of bug of missing url path in request()->url()
        if ( ! app()->runningInConsole() )
        {
            //set http request before laravel app starts
            //because missing url path bug in request()->url()
            $app->instance('request', \Illuminate\Http\Request::capture());
        }
    }

    /**
     * Parse given data into database format
     * @param  string/object $model
     * @param  array  $data
     * @param  string  $locale
     * @return array
     */
    public function buildDbData($model, $data = [], $locale = null)
    {
        $model = $this->getModelClass($model);

        foreach ($data as $key => $value)
        {
            //Data from belongs to many column are not stored in actual table
            if ( $model->hasFieldParam($key, ['belongsToMany']) ){
                unset($data[$key]);
                continue;
            }

            //Update checkbox values
            if ( $model->isFieldType($key, 'checkbox') ) {
                if ( $model->hasFieldParam($key, ['locale'], true) )
                {
                    foreach (array_wrap($data[$key]) as $k => $v) {
                        $data[$key][$k] = $v === true ? 1 : 0;
                    }
                } else {
                    $data[$key] = $value == true ? 1 : 0;
                }
            }

            //Update filled date format into date format from db
            if ( $model->isFieldType($key, 'date') && ! $model->hasFieldParam($key, ['locale'], true) )
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
            if ( $model->isFieldType($key, 'datetime') && ! $model->hasFieldParam($key, ['locale'], true) )
            {
                $data[$key] = $value ? Carbon::createFromFormat($model->getFieldParam($key, 'date_format'), $value)->format('Y-m-d H:i:s') : null;
            }

            //Update filled time format into date format from db
            if ( $model->isFieldType($key, 'time') && ! $model->hasFieldParam($key, ['multiple', 'locale'], true) )
            {
                $data[$key] = $value ? Carbon::createFromFormat($model->getFieldParam($key, 'date_format'), $value)->format('H:i:s') : null;
            }

            //Get key of select
            $data[$key] = $this->parseSelectValue($model, $key, $data[$key], true);

            //Set value as locale value
            if ( $model->hasFieldParam($key, 'locale', true) && $locale )
            {
                $data[$key] = [ $locale => $data[$key] ];
            }
        }

        return $data;
    }

    /**
     * Parse given data into relationship fields format
     * @param  string/object $model
     * @param  array  $data
     * @return array
     */
    public function buildRelationData($model, $data = [])
    {
        $model = $this->getModelClass($model);

        foreach ($data as $key => $value)
        {
            //Allow just columns with belongsToMany field type
            if ( !$model->hasFieldParam($key, ['belongsToMany']) )
            {
                unset($data[$key]);
                continue;
            }

            //Get option keys
            $data[$key] = $this->parseSelectValue($model, $key, $data[$key], true);
        }

        return $data;
    }

    /**
     * Fields belongsToMany values into db for given model
     * @param  string/class $model
     * @param  array $data
     * @return void
     */
    public function saveFieldRelationsValues($model, $data)
    {
        $model = $this->getModelClass($model);

        $data = $this->buildRelationData($model, $data);

        foreach ($data as $fieldKey => $values)
        {
            foreach ($values as $value)
            {
                $properties = $model->getRelationProperty($fieldKey, 'belongsToMany');

                DB::table($properties[3])->insert([
                    $properties[6] => 1,
                    $properties[7] => $value
                ]);
            }
        }
    }

    /**
     * Check if row exists
     * @param  string/object $model
     * @param  array  $data
     * @param  integer  $id
     * @return void
     */
    public function assertRowExists($model, $originalData = [], $id = null)
    {
        $model = $this->getModelClass($model);

        $data = $this->buildDbData($model, $originalData);

        $row = $id ? $model->select(array_keys($data))->where('id', $id)->first()->toArray()
                   : $model->select(array_keys($data))->first()->toArray();

        PHPUnit::assertEquals(
            $this->sortLocaleKeys($model, $row),
            $this->sortLocaleKeys($model, $data),
            'Table ['.$model->getTable().'] does not have excepted row'
        );

        $this->assertRowRelationsExists($model, $originalData, $id);

        return $this;
    }

    /**
     * Sort locale keys by asc
     * @param  string/object $model
     * @param  arrat $data
     * @return array
     */
    private function sortLocaleKeys($model, $data)
    {
        $model = $this->getModelClass($model);

        foreach ($data as $key => $value)
        {
            if ( $model->hasFieldParam($key, 'locale', true) )
                ksort($data[$key]);
        }

        return $data;
    }

    /**
     * Check given data represented as field relationships exists in actual row
     * @param  string/object $model
     * @param  array  $data
     * @param  integer  $id
     * @return void
     */
    public function assertRowRelationsExists($model, $data = [], $id = null)
    {
        $model = $this->getModelClass($model);

        $expected = $this->buildRelationData($model, $data);

        $actual = [];

        foreach ($expected as $key => $value)
        {
            $properties = $model->getRelationProperty($key, 'belongsToMany');

            $actual[$key] = DB::table($properties[3])->where($properties[6], $id)->get()->pluck($properties[7])->toArray();
        }

        PHPUnit::assertEquals(
            $expected, $actual,
            'Table ['.$model->getTable().'] does not have excepted row'
        );

        return $this;
    }

    /**
     * Merge created row, and updated data, and get result row
     * @param  string/object $model
     * @param  array  $row1
     * @param  array  $row2
     * @return array
     */
    public function createUpdatedRecord($model, $originalRow1, $row2)
    {
        $model = $this->getModelClass($model);

        $row1 = $originalRow1;

        foreach ($row2 as $key => $value)
        {
            if ( !is_array($row1[$key]) )
                $row1[$key] = $value;
            else {
                //We ned reset previous value if is select value or single relation type
                if (
                    ($model->isFieldType($key, 'select') && !$model->hasFieldParam($key, 'multiple'))
                    || $model->hasFieldParam($key, ['belongsTo', 'belongsToMany'])
                ) {
                    foreach ($row1[$key] as $k => $v)
                    {
                        //If is multiple value, we want remove same previous selected value from list
                        //If is single value, we want remove previous value
                        if ( !$model->hasFieldParam($key, ['array', 'multiple']) || array_key_exists($k, $row2[$key]) )
                            unset($row1[$key][$k]);
                    }
                }

                //If value from second array does not exists in first given array, then push it to array 1
                foreach ($value as $k => $v)
                    if ( !in_array($v, $originalRow1[$key]) )
                    {
                        if ( $this->isAssoc($value) )
                            $row1[$key][$k] = $v;
                        else
                            $row1[$key][] = $v;
                    }
            }
        }

        return $row1;
    }
}

?>