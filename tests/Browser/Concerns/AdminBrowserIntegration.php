<?php

namespace Admin\Tests\Browser\Concerns;

use Exception;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;

trait AdminBrowserIntegration
{
    /**
     * Returns model builder selector for laravel dusk
     *
     * @param  Admin\Eloquent\AdminModel  $model
     * @return string
     */
    public function getModelBuilderSelector($model)
    {
        return '@model-builder"][data-model="'.$model->getTable();
    }

    /**
     * Open model page.
     * @param  class $model
     * @return object
     */
    public function openModelPage($model)
    {
        $model = $this->getModelClass($model);

        $this->loginAs(User::first())
             ->visit(admin_action('DashboardController@index').'#/page/'.$model->getTable());

        //Wait till page loads and loader will disappear
        return $this->waitFor('h1')->waitUntilMissing('.overlay');
    }

    /**
     * Get table rows data in array.
     * @param  class $model
     * @param  string $wrapper
     * @return arrat
     */
    public function getRows($model, $wrapper = '')
    {
        $model = $this->getModelClass($model);

        $rows = $this->script("
            return (function(){
                var rows = $('{$wrapper}[data-table-rows=\"".$model->getTable()."\"] tbody tr'),
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
        foreach ($rows[0] as $item) {
            if (! array_key_exists($item[0], $array)) {
                $array[$item[0]] = [];
            }

            //Set keys and values
            foreach ($item[1] as $column) {
                $array[$item[0]][$column[0]] = $column[1];
            }
        }

        return $array;
    }

    /**
     * Change table rows limit.
     * @param  int $limit
     * @return void
     */
    public function changeRowsLimit($limit)
    {
        return $this->valueWithEvent('select[data-limit]', $limit, 'change');
    }

    /**
     * Change field value with event.
     * @param  string $selector
     * @param  string $value
     * @return object
     */
    public function valueWithEvent($selector, $value, $type = 'input')
    {
        $this->script("
            var e = document.createEvent('HTMLEvents');
                e.initEvent('{$type}', true, true);

            $('{$selector}').val('{$value}')[0].dispatchEvent(e);
        ");

        return $this;
    }

    /**
     * Fill form with array values.
     * @param  class $model
     * @param  array  $array
     * @param  string  $locale
     * @param  string  $wrapper
     * @return object
     */
    public function fillForm($model, $array = [], $locale = null, $wrapper = null)
    {
        $model = $this->getModelClass($model);

        $wrapper = $wrapper ? $wrapper.' ' : '';

        foreach ($array as $key => $value) {
            $formKey = $model->hasFieldParam($key, 'locale', true) ? $key.'['.$locale.']' : $key;
            $formKey = $this->modifyInParentKey($model, $formKey);

            //Set string value
            if (
                $model->isFieldType($key, ['string', 'longtext', 'text', 'integer', 'decimal', 'password'])
                && ! $model->hasFieldParam($key, 'multiple')
            ) {
                $hasComponent = $model->hasFieldParam($key, 'component');

                $inputWrapper = $hasComponent ? '' : '[data-model="'.$model->getTable().'"][data-field="'.$key.'"] ';

                $this->type($wrapper.$inputWrapper.'[name="'.$formKey.'"]', $value);
            }

            //Set editor value
            elseif ($model->isFieldType($key, ['longeditor', 'editor'])) {
                $this->with($wrapper.'textarea[name='.$formKey.']', function ($browser) use ($key, $value, $formKey) {
                    $browser->script('CKEDITOR.instances[$("[name=\''.$formKey.'\']").attr("id")].setData("'.$value.'")');
                });
            }

            //Set select value
            elseif ($model->isFieldType($key, ['select']) || $model->hasFieldParam($key, ['belongsTo', 'belongsToMany'])) {
                //Multiple select
                if ($model->hasFieldParam($key, ['multiple', 'array'])) {
                    //Select options in reversed order
                    foreach ($value as $k => $option_value) {
                        $selected = $this->script("return $('select[name=\"{$formKey}[]\"]').val()")[0];

                        //If value is selected already, we wante unselect given value
                        if ($this->isAssoc($value) && in_array($k, $selected)) {
                            $this->script("$('{$wrapper}select[name=\"{$formKey}[]\"]').parents('.form-group').eq(0).find('.chosen-choices li.search-choice:contains(\"{$option_value}\") a').eq(0).click()");
                        }

                        //Select given value
                        else {
                            $this->script("
                            $('{$wrapper}select[name=\"{$formKey}[]\"]').parents('.form-group').eq(0).each(function(){
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
                    if (is_array($value)) {
                        $value = array_values($value)[0];
                    }

                    $this->setChosenValue('select[name="'.$formKey.'"]', $value);
                }
            }

            //Set file value
            elseif ($model->isFieldType($key, ['file'])) {
                //Attach multiple files
                if ($model->hasFieldParam($key, 'multiple')) {
                    $this->attachMultiple($key.'[]', array_map(function ($file) {
                        return $this->getUploadFilePath($file);
                    }, $value));
                }

                //Attach single file
                else {
                    $this->attach($formKey, $this->getUploadFilePath($value));
                }
            }

            //Set checkbox value
            elseif ($model->isFieldType($key, ['checkbox'])) {
                $this->{$value === true || $value === 1 ? 'check' : 'uncheck'}($formKey);
            }

            //Set radio value
            elseif ($model->isFieldType($key, ['radio'])) {
                $this->radio($formKey, $value);
            }

            //Set multiple date value
            elseif ($model->isFieldType($key, 'date')) {
                //Fill multiple date
                if ($model->hasFieldParam($key, 'multiple')) {
                    foreach ($value as $date) {
                        $this->clickDatePicker($date, '[data-field="'.$formKey.'"]');
                    }
                }

                //Open calendar and click on specific date
                else {
                    //We need reset cursor before opening date and wait half of second till calendar opens
                    $this->resetFocus()
                         ->click($wrapper.'input[name="'.$formKey.'"]')
                         ->pause(500)
                         ->clickDatePicker($value);
                }
            }

            //Set datetime value
            elseif ($model->isFieldType($key, 'datetime')) {
                $date = explode(' ', $value);

                //We need reset cursor before opening date and wait half of second till calendar opens
                $this->resetFocus()
                     ->click($wrapper.'input[name="'.$formKey.'"]')
                     ->pause(500)
                     ->clickDatePicker($date[0])
                     ->clickTimePicker($date[1]);
            }

            //Set multiple time value
            elseif ($model->isFieldType($key, 'time')) {
                //Update multiple time value
                if ($model->hasFieldParam($key, 'multiple')) {
                    foreach ($value as $time) {
                        $this->clickTimePicker($time, '[data-field="'.$formKey.'"]');
                    }
                }

                //Open calendar and click on time
                else {
                    //We need reset cursor before opening date and wait half of second till calendar opens
                    $this->resetFocus()
                         ->click($wrapper.'input[name="'.$formKey.'"]')
                         ->pause(500)
                         ->clickTimePicker($value);
                }
            }
        }

        return $this->resetFocus()->pause(400);
    }

    /**
     * Get upload file path.
     * @param  string $file
     * @return string
     */
    private function getUploadFilePath($file)
    {
        //If is absolute path
        if ($file && $file[0] == '/') {
            return $file;
        }

        //Return admin stub files path
        if (method_exists($this, 'getUploadStubPath')) {
            return trim_end($this->getUploadStubPath(), '/').'/'.$file;
        }

        return __DIR__.'/../../Stubs/uploads/'.$file;
    }

    /**
     * Open row by given id.
     * @param  int $id
     * @param  string/object $model
     * @return object
     */
    public function openRow($id = null, $model = null)
    {
        $modelSelector = $model ? '[data-model="'.$this->getModelClass($model)->getTable().'"]' : '';

        //Open row
        $this->click($selector = '.buttons-options'.$modelSelector.' button[data-button="edit"][data-id="'.$id.'"]');

        //Wait till row will be opened
        $this->waitFor($selector.'.btn-success', 100);

        return $this;
    }

    /*
     * Resets focus by clicking to nowhere
     */
    public function resetFocus()
    {
        $this->script('if ("activeElement" in document) document.activeElement.blur();');

        return $this;
    }

    /*
     * Create jquery click
     */
    public function jsClick($selector)
    {
        $this->script('$("'.str_replace('"', "'", $selector).'")[0].click()');

        return $this;
    }

    /**
     * If is single model in relation support, add table prefix before key
     * @param  string|object $model
     * @param  string $key
     * @return string
     */
    public function modifyInParentKey($model, $key)
    {
        $model = $this->getModelClass($model);

        if (
            $model->getProperty('single') === true
            && $model->getProperty('inParent') === true
            && $model->getBelongsToRelation()
        ){
            $key = $model->getModelFormPrefix($key);
        }

        return $key;
    }

    /**
     * Return selectors to validation error message and error parent class.
     * @param  string $model
     * @param  string $key
     * @return array
     */
    private function getValidationErrorSelectors($model, $origKey)
    {
        $model = $this->getModelClass($model);
        $key = $origKey;

        //Add multiple key selector if missing
        if ($model->hasFieldParam($key, ['multiple', 'array']) && strpos($key, '[]') === false) {
            $key .= '[]';
        }

        $key = $this->modifyInParentKey($model, $key);

        //Reset error message selector and error class selector
        $selectorMessage = null;
        $selectorClass = null;

        if ($model->isFieldType($origKey, ['string', 'text', 'editor', 'integer', 'decimal', 'file', 'password', 'date', 'datetime', 'time'])) {
            $selectorMessage = "$('input[name=\"{$key}\"], textarea[name=\"{$key}\"]').parent()";
        }

        if ($model->isFieldType($origKey, ['select'])) {
            $selectorMessage = "$('select[name=\"{$key}\"]').parent().parent()";
        }

        if ($model->isFieldType($origKey, ['radio'])) {
            $selectorMessage = "$('input[name=\"{$key}\"]').parent().parent().parent()";
        }

        if ($model->isFieldType($origKey, ['file'])) {
            $selectorClass = "$('input[name=\"{$key}\"]').parent().parent()";
        }

        //Checkbox can not be required field
        if ($model->isFieldType($origKey, ['checkbox'])) {
            return false;
        }

        if (! $selectorMessage) {
            throw new Exception('Field ['.$key.'] in model ['.$model->getTable().'] is not valid type.');
        }

        //Check if element does not
        return [
            'key' => $key,
            'errorClass' => $this->script('return '.($selectorClass ? $selectorClass : $selectorMessage).'.hasClass("has-error")')[0],
            'errorMessage' => $this->script('return '.$selectorMessage.'.find("> span.help-block").length == 1')[0],
        ];
    }

    /**
     * Submit new instance of form.
     */
    public function submitForm($model = null)
    {
        $prefix = '';

        //If model is given, then restrict only given model button
        if ( $model && $model = $this->getModelClass($model) ) {
            $prefix = '[data-footer="'.$model->getTable().'"] ';
        }

        return $this->waitFor($prefix.'button[data-action-type="create"]')
                    ->press(trans('admin::admin.send'))
                    ->waitUntilMissing('button[data-action-type="loading"]')->pause(200);
    }

    /**
     * Submit existing instance of row.
     */
    public function saveForm()
    {
        return $this->waitFor('button[data-action-type="update"]')
                    ->press(trans('admin::admin.save'))
                    ->waitUntilMissing('button[data-action-type="loading"]');
    }

    /**
     * Close admin message alert.
     * @return string
     */
    public function closeAlert()
    {
        $this->whenAvailable('.modal', function($modal){
            $this->press(trans('admin::admin.close'));
        });

        return $this;
    }

    /**
     * Scroll to element.
     * @param  string $element
     * @return object
     */
    public function scrollToElement($element = null)
    {
        $this->script('$("html, body").animate({ scrollTop: $("'.($element ?: 'body').'").offset().top }, 0);');

        return $this;
    }

    /**
     * Click datepicker value.
     * @param  string $string d.m.Y
     * @param  string $selector
     * @return object
     */
    public function clickDatePicker($date, $selector = null)
    {
        $date = Carbon::createFromFormat('d.m.Y', $date);

        $this->script('$(\''.($selector ?: 'body > .xdsoft_datetimepicker ').' td[data-date="'.(int) $date->format('d').'"][data-month="'.((int) $date->format('m') - 1).'"][data-year="'.$date->format('Y').'"]:visible\').click()');

        return $this;
    }

    /**
     * Click datepicker value.
     * @param  string $string d.m.Y
     * @param  string $selector
     * @return object
     */
    public function clickTimePicker($time, $selector = null)
    {
        $time = explode(':', $time);

        $this->script('$(\''.($selector ?: 'body > .xdsoft_datetimepicker ').' div[data-hour="'.(int) $time[0].'"][data-minute="'.(int) $time[1].'"]:visible\').click()');

        return $this;
    }

    /**
     * Set chosenjs select value.
     * @param  string $selector
     * @param  string $value
     * @return object
     */
    public function setChosenValue($selector, $value = null)
    {
        $this->script($s = "
            var select = $('{$selector}');
            select.trigger('chosen:open').parent().each(function(){
                $(this).find('.chosen-results li:contains(\"{$value}\")')
                       .eq(0).mouseup()
            });
        ");

        $this->pause(100);

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

        $files = array_map(function ($file) {
            if (! is_file($file) || ! file_exists($file)) {
                throw new Exception('You may only upload existing files: '.$file);
            }

            return realpath($file);
        }, $paths);

        $element->sendKeys(implode("\n ", $files));

        return $this;
    }

    /**
     * Change form language.
     * @param  string $lang
     * @return object
     */
    public function changeRowLanguage($lang)
    {
        return $this->click('[data-form-language-switch] > button')->pause(100)
                    ->click('[data-form-language-switch] li[data-slug="'.$lang.'"]');
    }

    /**
     * Wait for the given selector to be visible.
     *
     * @param  string  $selector
     * @param  int  $seconds
     * @return $this
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function waitForElement($selector, $seconds = null)
    {
        $message = $this->formatTimeOutMessage('Waited %s seconds for selector', $selector);

        return $this->waitUsing($seconds, 100, function () use ($selector) {
            $element = $this->script("return $('".str_replace("'", "\'", $selector)."').length");

            return $element[0] > 0;
        }, $message);
    }
}
