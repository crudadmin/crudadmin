<?php

namespace Admin\Tests\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Assert as PHPUnit;

trait FeatureAssertions
{
    /**
     * Parse given data into database format.
     * @param  string/object $model
     * @param  array  $data
     * @param  string  $locale
     * @return array
     */
    public function buildDbData($model, $data = [], $locale = null)
    {
        $model = $this->getModelClass($model);

        foreach ($data as $key => $value) {
            //Data from belongs to many column are not stored in actual table
            if ($model->hasFieldParam($key, ['belongsToMany'])) {
                unset($data[$key]);
                continue;
            }

            //Update checkbox values
            if ($model->isFieldType($key, 'checkbox')) {
                if ($model->hasFieldParam($key, ['locale'], true)) {
                    foreach (array_wrap($data[$key]) as $k => $v) {
                        $data[$key][$k] = $v === true ? true : false;
                    }
                } else {
                    $data[$key] = $value == true ? true : false;
                }
            }

            //Update filled date format into date format from db
            if ($model->isFieldType($key, 'date') && ! $model->hasFieldParam($key, ['locale'], true)) {
                //Update multiple date field
                if ($model->hasFieldParam($key, 'multiple')) {
                    foreach ($value as $k => $date) {
                        $data[$key][$k] = Carbon::createFromFormat('d.m.Y', $date)->format('Y-m-d');
                    }
                }

                //Update single date
                else {
                    $data[$key] = $value ? Carbon::createFromFormat($model->getFieldParam($key, 'date_format'), $value)->setTime(0,0,0)->toISOString() : null;
                }
            }

            //Update filled datetime format into date format from db
            if ($model->isFieldType($key, 'datetime') && ! $model->hasFieldParam($key, ['locale'], true)) {
                $data[$key] = $value ? Carbon::createFromFormat($model->getFieldParam($key, 'date_format'), $value)->toISOString() : null;
            }

            //Update filled time format into date format from db
            if ($model->isFieldType($key, 'time') && ! $model->hasFieldParam($key, ['multiple', 'locale'], true)) {
                $data[$key] = $value ? Carbon::createFromFormat($model->getFieldParam($key, 'date_format'), $value)->format('H:i:s') : null;
            }

            //Get key of select
            $data[$key] = $this->parseSelectValue($model, $key, $data[$key], true);

            //Set value as locale value
            if ($model->hasFieldParam($key, 'locale', true) && $locale) {
                $data[$key] = [$locale => $data[$key]];
            }
        }

        return $data;
    }

    /**
     * Parse given data into relationship fields format.
     * @param  string/object $model
     * @param  array  $data
     * @return array
     */
    public function buildRelationData($model, $data = [])
    {
        $model = $this->getModelClass($model);

        foreach ($data as $key => $value) {
            //Allow just columns with belongsToMany field type
            if (! $model->hasFieldParam($key, ['belongsToMany'])) {
                unset($data[$key]);
                continue;
            }

            //Get option keys
            $data[$key] = $this->parseSelectValue($model, $key, $data[$key], true);
        }

        return $data;
    }

    /**
     * Fields belongsToMany values into db for given model.
     * @param  string/class $model
     * @param  array $data
     * @return void
     */
    public function saveFieldRelationsValues($model, $data)
    {
        $model = $this->getModelClass($model);

        $data = $this->buildRelationData($model, $data);

        foreach ($data as $fieldKey => $values) {
            foreach ($values as $value) {
                $properties = $model->getRelationProperty($fieldKey, 'belongsToMany');

                DB::table($properties[3])->insert([
                    $properties[6] => 1,
                    $properties[7] => $value,
                ]);
            }
        }
    }

    /**
     * Sort locale keys by asc.
     * @param  string/object $model
     * @param  arrat $data
     * @return array
     */
    private function sortLocaleKeys($model, $data)
    {
        $model = $this->getModelClass($model);

        foreach ($data as $key => $value) {
            if ($model->hasFieldParam($key, 'locale', true)) {
                ksort($data[$key]);
            }
        }

        return $data;
    }

    /**
     * Check if row exists.
     * @param  string/object $model
     * @param  array  $data
     * @param  int  $id
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
     * Check given data represented as field relationships exists in actual row.
     * @param  string/object $model
     * @param  array  $data
     * @param  int  $id
     * @return void
     */
    public function assertRowRelationsExists($model, $data = [], $id = null)
    {
        $model = $this->getModelClass($model);

        $expected = $this->buildRelationData($model, $data);

        $actual = [];

        foreach ($expected as $key => $value) {
            $properties = $model->getRelationProperty($key, 'belongsToMany');

            $actual[$key] = DB::table($properties[3])->where($properties[6], $id)->get()->pluck($properties[7])->toArray();
        }

        PHPUnit::assertEquals(
            $expected, $actual,
            'Table ['.$model->getTable().'] does not have excepted row'
        );

        return $this;
    }
}
