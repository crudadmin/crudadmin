<?php

namespace Admin\Tests\Concerns;

use Admin\Tests\Concerns\DropDatabase;
use Admin\Tests\Concerns\DropUploads;

trait AdminIntegration
{
    /**
     * Setup the test environment.
     */
    protected function tearDown() : void
    {
        $this->registerTraits();

        parent::tearDown();
    }

    /*
     * Register all traits instances
     */
    protected function registerTraits()
    {
        $uses = array_flip(class_uses_recursive(static::class));

        //Registers own event for dropping database after test
        if (isset($uses[DropDatabase::class])) {
            $this->dropDatabase();
        }

        // //Registers own event for dropping uploads data after test
        if (isset($uses[DropUploads::class])) {
            $this->dropUploads();
        }
    }

    /**
     * Return object of class
     * @param  string/object $model
     * @return object
     */
    private function getModelClass($model)
    {
        return is_object($model) ? $model : new $model;
    }

    /*
     * Check if is array associative
     */
    protected function isAssoc(array $arr)
    {
        if ([] === $arr)
            return false;

        if ( array_keys($arr) !== range(0, count($arr) - 1) )
            return true;

        return false;
    }

    /**
     * Parse select/multiselect values/keys to correct format
     * Sometimes we need just select keys, or select values
     * @param  string/object    $model
     * @param  string           $key
     * @param  mixed            $value
     * @param  boolean            $returnKey
     * @return mixed
     */
    protected function parseSelectValue($model, $key, $value, $returnKey = false)
    {
        $model = $this->getModelClass($model);

        if (
            ($model->isFieldType($key, 'select') || $model->hasFieldParam($key, ['belongsTo', 'belongsToMany']))
            && !$model->hasFieldParam($key, ['locale'], true)
        )
        {
            if ( is_array($value) && $this->isAssoc($value) )
            {
                $items = $returnKey ? array_keys($value) : array_values($value);

                $value = $model->hasFieldParam($key, ['belongsTo']) ? $items[0] : $items;
            }
        }

        return $value;
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