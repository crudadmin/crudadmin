<?php

namespace Gogol\Admin\Tests\Browser;

use Carbon\Carbon;
use Exception;
use Facebook\WebDriver\Remote\LocalFileDetector;
use Gogol\Admin\Tests\Traits\AdminTrait;
use Illuminate\Foundation\Auth\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Assert as PHPUnit;

class DuskBrowser extends Browser
{
    use AdminTrait;

    /**
     * Open model page
     * @param  class $model
     * @return object
     */
    public function openModelPage($model)
    {
        $model = $this->getModelClass($model);

        $this->loginAs(User::first())
             ->visit(admin_action('DashboardController@index'))
             ->clickLink($model->getProperty('name'));

        return $this;
    }

    /**
     * Get rows data in array
     * @param  class $model
     * @return arrat
     */
    public function getRows($model)
    {
        $model = $this->getModelClass($model);

        $rows = $this->script("
            return (function(){
                var rows = $('[data-table-rows=\"".$model->getTable()."\"] tbody tr'),
                    data = [];

                for ( var i = 0; i < rows.length; i++ )
                {
                    var columns = $(rows[i]).find('td[data-field]'),
                        col_data = [];

                    for ( var a = 0; a < columns.length; a++ )
                        col_data.push([$(columns[a]).attr('data-field'), $(columns[a]).text().replace(/\s+/g, ' ').trim()]);

                    data.push([$(rows[i]).attr('data-id'), col_data]);
                }

                return data;
            })();
        ");

        $array = [];

        //Fix order of keys from row columns
        foreach ($rows[0] as $item)
        {
            if ( ! array_key_exists($item[0], $array) )
                $array[$item[0]] = [];

            //Set keys and values
            foreach ($item[1] as $column)
            {
                $array[$item[0]][$column[0]] = $column[1];
            }
        }

        return $array;
    }

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

        //Mutate diven rows data
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

        PHPUnit::assertEquals($this->getRows($model)[$id], $row, 'Row '.$id.' does not match diven data in table rows.');

        return $this;
    }

    /**
     * Change table rows limit
     * @param  integer $limit
     * @return void
     */
    public function changeRowsLimit($limit)
    {
        return $this->valueWithEvent('select[data-limit]', $limit, 'change');
    }

    /**
     * Change field value with event
     * @param  string $selector
     * @param  string $value
     * @return object
     */
    public function valueWithEvent($selector, $value, $type = 'input')
    {
        $this->script("
            var e = document.createEvent('HTMLEvents');
                e.initEvent('{$type}', true, true);

            $('{$selector}').val({$value})[0].dispatchEvent(e);
        ");

        return $this;
    }

    /**
     * Fill form with array values
     * @param  class $model
     * @param  array  $array
     * @return object
     */
    public function fillForm($model, $array = [])
    {
        $model = $this->getModelClass($model);

        foreach ($array as $key => $value)
        {
            //Set string value
            if (
                $model->isFieldType($key, ['string', 'longtext', 'text', 'integer', 'decimal', 'password'])
                && ! $model->hasFieldParam($key, 'multiple')
            ) {
                $this->type($key, $value);
            }

            //Set editor value
            else if ( $model->isFieldType($key, ['longeditor', 'editor']) ) {
                $this->with('textarea[name='.$key.']', function($browser) use ($key, $value) {
                    $browser->script('CKEDITOR.instances[$("[name='.$key.']").attr("id")].setData("'.$value.'")');
                });
            }

            //Set select value
            else if ( $model->isFieldType($key, ['select']) || $model->hasFieldParam($key, ['belongsTo', 'belongsToMany']) )
            {
                //Multiple select
                if ( $model->hasFieldParam($key, ['multiple', 'array']) )
                {
                    //Select options in reversed order
                    foreach ($value as $k => $option_value)
                    {
                        $selected = $this->script("return $('select[name=\"{$key}[]\"]').val()")[0];

                        //If value is selected already, we wante unselect given value
                        if ( $this->isAssoc($value) && in_array($k, $selected) )
                        {
                            $this->script("$('select[name=\"{$key}[]\"]').parents('.form-group').eq(0).find('.chosen-choices li.search-choice:contains(\"{$option_value}\") a').eq(0).click()");
                        }

                        //Select given value
                        else {
                            $this->script("
                            $('select[name=\"{$key}[]\"]').parents('.form-group').eq(0).each(function(){
                                $(this).find('input').click().focus();
                                $(this).find('.chosen-results li:contains(\"{$option_value}\")').eq(0).mouseup()
                            });
                            ");
                        }

                        $this->pause(100);
                    }
                }

                //Single select
                else {
                    //If is associative value with key, then we want only value
                    if ( is_array($value) )
                        $value = array_values($value)[0];

                    $this->script($s = "
                        var select = $('select[name=\"{$key}\"]');
                        select.trigger('chosen:open').parents('.form-group').eq(0).each(function(){
                            $(this).find('.chosen-results li:contains(\"{$value}\")').eq(0).mouseup()
                        });
                    ");

                    $this->pause(100);
                }
            }

            //Set file value
            else if ( $model->isFieldType($key, ['file']) )
            {
                //Attach multiple files
                if ( $model->hasFieldParam($key, 'multiple') )
                {
                    $this->attachMultiple($key.'[]', array_map(function($file){
                        return $this->getStubPath('/uploads/'.$file);
                    }, $value));
                }

                //Attach single file
                else {
                    $this->attach($key, $this->getStubPath('/uploads/'.$value));
                }
            }

            //Set checkbox value
            else if ( $model->isFieldType($key, ['checkbox']) ) {
                $this->{$value === true || $value === 1 ? 'check' : 'uncheck'}($key);
            }

            //Set radio value
            else if ( $model->isFieldType($key, ['radio']) ) {
                $this->radio($key, $value);
            }

            //Set multiple date value
            else if ( $model->isFieldType($key, 'date') )
            {
                //Fill multiple date
                if ( $model->hasFieldParam($key, 'multiple') )
                {
                    foreach ($value as $date)
                    {
                        $this->clickDatePicker($date, '[data-field="'.$key.'"]');
                    }
                }

                //Open calendar and click on specific date
                else {
                    //We need reset cursor before opening date and wait half of second till calendar opens
                    $this->resetFocus()
                         ->click('input[name="'.$key.'"]')
                         ->pause(500)
                         ->clickDatePicker($value);
                }
            }

            //Set datetime value
            else if ( $model->isFieldType($key, 'datetime') )
            {
                $date = explode(' ', $value);

                //We need reset cursor before opening date and wait half of second till calendar opens
                $this->resetFocus()
                     ->click('input[name="'.$key.'"]')
                     ->pause(500)
                     ->clickDatePicker($date[0])
                     ->clickTimePicker($date[1]);
            }

            //Set multiple time value
            else if ( $model->isFieldType($key, 'time') )
            {
                //Update multiple time value
                if ( $model->hasFieldParam($key, 'multiple') )
                {
                    foreach ($value as $time)
                       $this->clickTimePicker($time, '[data-field="'.$key.'"]');
                }

                //Open calendar and click on time
                else {
                    //We need reset cursor before opening date and wait half of second till calendar opens
                    $this->resetFocus()
                         ->click('input[name="'.$key.'"]')
                         ->pause(500)
                         ->clickTimePicker($value);
                }
            }
        }

        return $this->pause(300);
    }

    /*
     * Resets focus by clicking to nowhere
     */
    public function resetFocus()
    {
        return $this->click('h1');
    }

    /**
     * Check if form has filled form values
     * @param  class $model
     * @param  array  $array
     * @return object
     */
    public function assertHasFormValues($model, $array = [])
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

            $this->assertVue('row.'.$key, $value, '@model-builder');
            $this->assertVue('model.fields.'.$key.'.value', $value, '@model-builder');
        }

        return $this;
    }

    /**
     * Check if form is empty and has no values
     * @param  class $model
     * @param  array  $array
     * @return object
     */
    public function assertFormIsEmpty($model)
    {
        $model = $this->getModelClass($model);

        foreach ($model->getFields() as $key => $value)
        {
            //Check if input has empty value
            $this->assertEmptyValue($model, $key);
        }

        return $this;
    }

    /**
     * Check if input type is empty
     * @param  class $model
     * @param  array  $array
     * @return object
     */
    public function assertEmptyValue($model, $key)
    {
        $model = $this->getModelClass($model);

        //Create multiple key selector for multiple type fields
        $selectorKey = ($isMultiple = $model->hasFieldParam($key, ['multiple'])) ? $key.'[]' : $key;

        //In some types of elements we need search for checked elements
        $stateSelector = $model->isFieldType($key, ['radio', 'checkbox']) ? ':checked' : '';

        //Get jquery value
        $actual = $this->script('return $("input[name=\"'.$selectorKey.'\"]'.$stateSelector.', select[name=\"'.$selectorKey.'\"], textarea[name=\"'.$selectorKey.'\"]").eq(0).val();')[0];

        //If input is multiple select type, then we need check if value is empty array
        $expected = $isMultiple && $model->isFieldType($key, 'select') ? [] : null;

        //Check javascript input value
        PHPUnit::assertEquals($expected, $actual, 'Input ['.$selectorKey.'] is not empty in ['.$model->getTable().'] form.');

        //Check vuejs values
        $this->assertVue('row.'.$key, null, '@model-builder');
        $this->assertVue('model.fields.'.$key.'.value', null, '@model-builder');

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
     * Check if table rows has visible given fields
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
     * Return selectors to validation error message and error parent class
     * @param  string $model
     * @param  string $key
     * @return array
     */
    private function getValidationErrorSelectors($model, $origKey)
    {
        $model = $this->getModelClass($model);
        $key = $origKey;

        //Add multiple key selector if missing
        if ( $model->hasFieldParam($key, ['multiple', 'array']) && strpos($key, '[]') === false ){
            $key .= '[]';
        }

        //Reset error message selector and error class selector
        $selectorMessage = null;
        $selectorClass = null;

        if ( $model->isFieldType($origKey, ['string', 'text', 'editor', 'integer', 'decimal', 'file', 'password', 'date', 'datetime', 'time']) )
            $selectorMessage = "$('input[name=\"{$key}\"], textarea[name=\"{$key}\"]').parent()";

        if ( $model->isFieldType($origKey, ['select']) )
            $selectorMessage = "$('select[name=\"{$key}\"]').parent().parent()";

        if ( $model->isFieldType($origKey, ['radio']) )
            $selectorMessage = "$('input[name=\"{$key}\"]').parent().parent().parent()";

        if ( $model->isFieldType($origKey, ['file']) )
            $selectorClass = "$('input[name=\"{$key}\"]').parent().parent()";

        //Checkbox can not be required field
        if ( $model->isFieldType($origKey, ['checkbox']) )
            return false;

        if ( ! $selectorMessage )
            throw new Exception('Field ['.$key.'] in model ['.$model->getTable().'] is not valid type.');

        //Check if element does not
        return [
            'key' => $key,
            'errorClass' => $this->script('return '.($selectorClass ? $selectorClass : $selectorMessage).'.hasClass("has-error")')[0],
            'errorMessage' => $this->script('return '.$selectorMessage.'.find("> span.help-block").length == 1')[0]
        ];
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
     * Submit new instance of form
     */
    public function submitForm()
    {
        return $this->press(trans('admin::admin.send'));
    }

    /**
     * Submit existing instance of row
     */
    public function saveForm()
    {
        return $this->press(trans('admin::admin.save'));
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
     * Close admin message alert
     * @return string
     */
    public function closeAlert()
    {
        $this->press(trans('admin::admin.close'));

        return $this;
    }

    /**
     * Scroll to element
     * @param  string $element
     * @return object
     */
    public function scrollToElement($element = null)
    {
        $this->script('$("html, body").animate({ scrollTop: $("'.($element?:'body').'").offset().top }, 0);');

        return $this;
    }

    /**
     * Open admin row
     * @param  [type] $element
     * @return [type]
     */
    public function openRow($id = null)
    {
        //Open row
        $this->click($selector = '.buttons-options button[data-button="edit"][data-id="'.$id.'"]');

        //Wait till row will be opened
        $this->waitFor($selector.'.btn-success');

        return $this;
    }

    /**
     * Click datepicker value
     * @param  string $string d.m.Y
     * @param  string $selector
     * @return object
     */
    public function clickDatePicker($date, $selector = null)
    {
        $date = Carbon::createFromFormat('d.m.Y', $date);

        $this->script($s = '$(\''.($selector ?: 'body > .xdsoft_datetimepicker ').' td[data-date="'.(int)$date->format('d').'"][data-month="'.((int)$date->format('m')-1).'"][data-year="'.$date->format('Y').'"]:visible\').click()');

        return $this;
    }

    /**
     * Click datepicker value
     * @param  string $string d.m.Y
     * @param  string $selector
     * @return object
     */
    public function clickTimePicker($time, $selector = null)
    {
        $time = explode(':', $time);

        $this->script('$(\''.($selector ?: 'body > .xdsoft_datetimepicker ').' div[data-hour="'.(int)$time[0].'"][data-minute="'.(int)$time[1].'"]:visible\').click()');

        return $this;
    }

    /**
     * Attach the given files into to the field.
     *
     * @param  string  $field
     * @param  array  $paths
     * @return $this
     */
    public function attachMultiple($field, array $paths = [])
    {
        $element = $this->resolver->resolveForAttachment($field);

        $files = array_map(function($file){
            if (! is_file($file) || ! file_exists($file)) {
                throw new Exception('You may only upload existing files: '.$file);
            }

            return realpath($file);
        }, $paths);

        $element->sendKeys(implode("\n ", $files));

        return $this;
    }
}