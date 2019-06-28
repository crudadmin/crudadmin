<?php

namespace Admin\Tests\Browser\Concerns;

use Carbon\Carbon;
use PHPUnit\Framework\Assert as PHPUnit;

trait AdminBrowserAssertions
{
    /**
     * Check row exists in table rows
     * @param  class $model
     * @param  array $array
     * @param  integer  $id
     * @return object
     */
    public function assertTableRowExists($model, $row = [], $id = null)
    {
        $model = $this->getModelClass($model);

        //Mutate given rows data
        foreach ($row as $key => $value)
        {
            //Update checkbox values
            if ( $model->isFieldType($key, ['checkbox']) )
                $row[$key] = $value ? trans('admin::admin.yes') : trans('admin::admin.no');

            //Get field select values
            $row[$key] = $this->parseSelectValue($model, $key, $row[$key]);

            //Everything need to be string
            if ( is_array($row[$key]) )
                $row[$key] = implode(', ', $row[$key]);
            else if ( is_numeric($row[$key]) )
                $row[$key] = (string)$row[$key];
        }

        //Get id from row, if is not given
        if ( ! $id )
            $id = $row['id'];

        $actual = $this->getRows($model)[$id];

        PHPUnit::assertEquals(array_keys($actual), array_keys($row), 'Row '.$id.' does not match given order of columns in data table row.');
        PHPUnit::assertEquals($actual, $row, 'Row '.$id.' does not match given data in data table rows.');

        return $this;
    }


    /**
     * Check row data are sorted in specific order
     * @param  class $model
     * @param  string $column
     * @param  string  $type
     * @return object
     */
    public function assertRowsSortOrder($model, $column, $type = 'asc')
    {
        $model = $this->getModelClass($model);

        $rows = $this->getRows($model);

        $actual = array_map(function($item) use ($column) {
            return $item[$column];
        }, $rows);

        $excepted = $actual;

        //Sort date values by given type
        if ( $model->isFieldType($column, ['date', 'datetime']) )
        {
            $this->sortDateTimes($excepted, $type == 'asc');
        }

        //Sort texts and numbers by given type
        else {
            if ( $type == 'desc' )
                arsort($excepted);
            else
                asort($excepted);
        }

        PHPUnit::assertEquals($actual, $excepted, 'Sorting columns for column ['.$column.'] does not work in ['.$type.'] order.');

        return $this;
    }

    /**
     * Sort date and datetimes
     * @param  array   $dates
     * @param  boolean $asc
     * @return array
     */
    private function sortDateTimes($dates = [], $asc = true)
    {
        return usort($dates, function($a, $b) use ($asc) {
            $a = explode(' ', $a);
            $b = explode(' ', $b);

            //Change format from d.m.y to y.m.d
            $a[0] = strrev($a[0]);
            $b[0] = strrev($b[0]);

            $a = implode(' ', $a);
            $b = implode(' ', $b);

            if ($a == $b) {
                return 0;
            }

            return (($a < $b) ? -1 : 1) * ($asc === false ? -1 : 1);
        });
    }

    /**
     * Check if form has filled form values
     * @param  class $model
     * @param  array  $array
     * @param  string  $locale
     * @return object
     */
    public function assertHasFormValues($model, $array = [], $locale = null)
    {
        $model = $this->getModelClass($model);

        foreach ($array as $key => $value)
        {
            //Editor and file are not binding row values for now
            if ( $model->isFieldType($key, ['editor', 'file']) )
                continue;

            //Update date multiple values
            if ( $model->isFieldType($key, 'date') && $model->hasFieldParam($key, 'multiple') )
            {
                foreach ($value as $k => $date)
                {
                    $value[$k] = Carbon::createFromFormat('d.m.Y', $date)->format($model->getFieldParam($key, 'date_format'));
                }
            }

            //If is associative array in select field type, then we need compare keys, not values
            $value = $this->parseSelectValue($model, $key, $value, true);

            $hasLocale = $model->hasFieldParam($key, 'locale', true);

            $this->assertVue('row.'.$key.($locale && $hasLocale ? '.'.$locale : ''), $value, '@model-builder');
            $this->assertVue('model.fields.'.$key.'.value'.($locale && $hasLocale ? '.'.$locale : ''), $value, '@model-builder');
        }

        return $this;
    }

    /**
     * Check if form is empty and has no values
     * @param  class  $model
     * @param  string $locale
     * @return object
     */
    public function assertFormIsEmpty($model, $locale = null)
    {
        $model = $this->getModelClass($model);

        foreach ($model->getFields() as $key => $value)
        {
            //Check if input has empty value
            $this->assertEmptyValue($model, $key, $locale);
        }

        return $this;
    }

    /**
     * Check if given input in model is empty
     * @param  class $model
     * @param  string  $key
     * @param  string  $locale
     * @return object
     */
    public function assertEmptyValue($model, $key, $locale = null)
    {
        $model = $this->getModelClass($model);

        //Create multiple key selector for multiple type fields
        $selectorKey = $locale ? $key.'['.$locale.']' : '';
        $selectorKey = ($isMultiple = $model->hasFieldParam($key, ['multiple'])) ? $key.'[]' : $key;

        //In some types of elements we need search for checked elements
        $stateSelector = $model->isFieldType($key, ['radio', 'checkbox']) ? ':checked' : '';

        //Get jquery value
        $actual = $this->script('return $("input[name=\"'.$selectorKey.'\"]'.$stateSelector.', select[name=\"'.$selectorKey.'\"], textarea[name=\"'.$selectorKey.'\"]").eq(0).val();')[0];

        //If input is multiple select type, then we need check if value is empty array
        $expected = $isMultiple && $model->isFieldType($key, 'select') ? [] : null;

        //Check javascript input value
        PHPUnit::assertEquals($expected, $actual, 'Input ['.$selectorKey.'] is not empty in ['.$model->getTable().'] form.');

        $row = $this->vueAttribute('@model-builder', $rowKey = 'row.'.$key);
        $value = $this->vueAttribute('@model-builder', $valueKey = 'model.fields.'.$key.'.value');

        //Check vuejs row value
        if ( $locale && is_array($row) ) {
            $this->assertVue($rowKey.'.'.$locale, null, '@model-builder');
        } else {
            $this->assertVue($rowKey, null, '@model-builder');
        }

        //Check vuejs field value
        if ( $locale && is_array($value) ) {
            $this->assertVue($valueKey.'.'.$locale, null, '@model-builder');
        } else {
            $this->assertVue($valueKey, null, '@model-builder');
        }

        return $this;
    }


    /**
     * Check if element has class
     * @param  class $element
     * @param  array  $class
     * @return object
     */
    public function assertHasAttribute($element, $attribute, $value = null)
    {
        $query = $this->script('return $(\''.$element.'\').eq(0).attr("'.$attribute.'")')[0];

        //If value is empty, we want check if attribute is equal to empty string
        //if query value is empty string, this means that attribute is available
        if ( $value === null )
            $value = '';

        PHPUnit::assertSame(
            $query, $value,
            'Attribute ['.$attribute.'] does not '.($value ? "match value [$value]" : 'exists').' in element ['.$element.']'
        );

        return $this;
    }

    /**
     * Check if element does not have class
     * @param  class $element
     * @param  string  $attribute
     * @param  string  $value
     * @return object
     */
    public function assertHasNotAttribute($element, $attribute, $value = null)
    {
        $query = $this->script('return $(\''.$element.'\').eq(0).attr("'.$attribute.'")')[0];

        //If we want check if attribute does not exists
        //then we need check query value, if value is empty string, this means that atribute is available
        //in this case we need change query to null, for passing test
        $query = $value === null ? ($query === "" ? null : "") : $query;

        PHPUnit::assertNotSame(
            $query, $value,
            'Attribute ['.$attribute.'] does '.($value !== null ? "match value [$value]" : 'exists').' in element ['.$element.']'
        );

        return $this;
    }

    /**
     * Check if element has class
     * @param  class $element
     * @param  array  $class
     * @return object
     */
    public function assertHasClass($element, $class, $has_not = false)
    {
        $query = $this->script('return $(\''.$element.'\').eq(0).attr("class")');

        $classes = count($query) > 0 ? explode(' ', (string)$query[0]) : [];

        PHPUnit::assertContains(
            $class, $classes,
            'Class ['.$class.'] does not exists in element ['.$element.']'
        );

        return $this;
    }

    /**
     * Check if element does not have class
     * @param  class $element
     * @param  array  $class
     * @return object
     */
    public function assertHasNotClass($element, $class)
    {
        $query = $this->script('return $(\''.$element.'\').eq(0).attr("class")');

        $classes = count($query) > 0 ? explode(' ', (string)$query[0]) : [];

        PHPUnit::assertNotContains(
            $class, $classes,
            'Class ['.$class.'] does exists in element ['.$element.']'
        );

        return $this;
    }

    /**
     * Check if given field is visible in columns list
     * @param  class $model
     * @param  array  $array
     * @return object
     */
    public function assertVisibleColumnsList($model, $excepted = [])
    {
        $model = $this->getModelClass($model);

        $columns = $this->script("return $('[data-table-rows=\"".$model->getTable()."\"] thead th').map(function(){
            return $(this).attr('class')
        })");

        PHPUnit::assertEquals(
            $columns[0], array_map(function($item){
                return 'th-'.$item;
            }, array_values(array_merge($excepted, ['options-buttons']))),
            'Table ['.$model->getTable().'] does not match excepted columns list'
        );

        return $this;
    }

    /**
     * Check if values of all rows from one field in specific column contains of given data
     * @param  class $model
     * @param  string $column
     * @param  array $excepted
     * @param  boolean $equals
     * @return object
     */
    public function assertColumnRowData($model, $column, $excepted = [], $equals = true)
    {
        $model = $this->getModelClass($model);

        $columns = $this->script("return $('[data-table-rows=\"".$model->getTable()."\"] thead th').map(function(){
            return $(this).attr('class')
        })");

        $rows = $this->getRows($model);

        PHPUnit::{$equals ? 'assertEquals' : 'assertNotEquals'}(
            $excepted,
            array_values(array_map(function($item) use($column) {
                return $item[$column];
            }, $rows)),
            "Column [{$column}] in model [{$model->getTable()}] does ".($equals ? 'not ' : '')."contains of given values."
        );

        return $this;
    }

    /**
     * Check if values in specific columns does not contains of given data
     * @param  class $model
     * @param  string $column
     * @param  array $excepted
     * @return object
     */
    public function assertColumnRowDataNotEquals($model, $column, $excepted = [], $equals = true)
    {
        $this->assertColumnRowData($model, $column, $excepted, false);

        return $this;
    }


    /**
     * Check if given select has correct values
     * @param  class $model
     * @param  string $key
     * @param  array $excepted
     * @return object
     */
    public function assertSelectValues($model, $key, $excepted = [])
    {
        $model = $this->getModelClass($model);

        $options = $this->script("return $('[data-model=\"".$model->getTable()."\"][data-field=\"$key\"] select[name=\"$key\"] option').map(function(){
            return $(this).text();
        })")[0];

        PHPUnit::assertEquals(
            array_values(array_merge([trans('admin::admin.select-option')], $excepted)),
            $options,
            "Select [{$key}] in model [{$model->getTable()}] does not have given values."
        );

        return $this;
    }

    /**
     * Check if input does have validation error
     * @param  class $model
     * @param  array $array
     * @return object
     */
    public function assertDoesNotHaveValidationError($model, $fields)
    {
        foreach ($fields as $key)
        {
            //Skip non validation inputs
            if ( ($selectors = $this->getValidationErrorSelectors($model, $key)) === false )
                continue;

            PHPUnit::assertFalse($selectors['errorClass'], 'Field ['.$selectors['key'].'] does have validation error class.');
            PHPUnit::assertFalse($selectors['errorMessage'], 'Field ['.$selectors['key'].'] does have validation error message.');
        }

        return $this;
    }

    /**
     * Check if input have validation error
     * @param  class $model
     * @param  array $array
     * @return object
     */
    public function assertHasValidationError($model, $fields)
    {
        foreach ($fields as $key)
        {
            //Skip non validation inputs
            if ( ($selectors = $this->getValidationErrorSelectors($model, $key)) === false )
                continue;

            PHPUnit::assertTrue($selectors['errorClass'], 'Field ['.$selectors['key'].'] does not have validation error class.');
            PHPUnit::assertTrue($selectors['errorMessage'], 'Field ['.$selectors['key'].'] does not have validation error message.');
        }

        return $this;
    }

    /**
     * Check if success message exists
     * @return string
     */
    public function assertSeeSuccess($message, $seconds = null)
    {
        $this->waitForText($message ?: trans('admin::admin.success-created'), $seconds)
             ->assertSee($message ?: trans('admin::admin.success-created'));

         return $this;
    }

   /**
     * Check if given element exists
     * @param  string  $query
     * @return void
     */
    public function assertElementExists($query)
    {
        $element = $this->script('return $("'.str_replace('"', '\"', $query).'").length;');

        PHPUnit::assertTrue($element[0] > 0, 'Element ['.$query.'] does not exists');

        return $this;
    }

    /**
     * Check if given element does not exists
     * @param  string  $query
     * @return void
     */
    public function assertElementDoesNotExists($query)
    {
        $element = $this->script('return $("'.str_replace('"', '\"', $query).'").length;');

        PHPUnit::assertFalse($element[0] > 0, 'Element ['.$query.'] does not exists');

        return $this;
    }
}