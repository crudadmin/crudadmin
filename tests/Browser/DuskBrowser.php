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
                        col_data.push([$(columns[a]).attr('data-field'), $(columns[a]).text()]);

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
                $model->isFieldType($key, ['string', 'longtext', 'text', 'integer', 'decimal', 'password', 'date', 'datetime', 'time'])
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
            else if ( $model->isFieldType($key, ['select']) )
            {
                //Multiple select
                if ( $model->hasFieldParam($key, 'multiple') )
                {
                    //Select options in reversed order
                    foreach ($value as $k => $option_value)
                    {
                        $this->script("
                        $('select[name=\"{$key}[]\"]').parents('.form-group').eq(0).each(function(){
                            $(this).find('input').click().focus();
                            $(this).find('.chosen-results li:contains(\"{$option_value}\")').mouseup()
                        });
                        ");
                        $this->pause(100);
                    }
                }

                //Single select
                else {
                    $this->value('select[name="'.$key.'"]', $value)
                         ->with('select[name='.$key.']', function($browser) use ($key) {
                             $browser->script('$("select[name='.$key.']").change().trigger("chosen:updated");');
                         });
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
            else if ( $model->isFieldType($key, 'date') && $model->hasFieldParam($key, 'multiple') )
            {
                foreach ($value as $date)
                {
                    $date = Carbon::createFromFormat('d.m.Y', $date);

                    $this->clickDatePicker($date->format('d.m.Y'), '[data-field="'.$key.'"]');
                }
            }

            //Set multiple time value
            else if ( $model->isFieldType($key, 'time') && $model->hasFieldParam($key, 'multiple') )
            {
                foreach ($value as $time)
                {
                    $time = explode(':', $time);

                    $this->script('$(\'[data-field="'.$key.'"]\').find(\'div[data-hour="'.(int)$time[0].'"][data-minute="'.(int)$time[1].'"]\').click()');
                }
            }
        }

        return $this->pause(300);
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

            $this->assertVue('row.'.$key, $value, '@model-builder');
            $this->assertVue('model.fields.'.$key.'.value', $value, '@model-builder');
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
            }, array_merge($excepted, ['options-buttons'])),
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

        $this->script('$(\''.($selector ?: 'body').' td[data-date="'.(int)$date->format('d').'"][data-month="'.((int)$date->format('m')-1).'"][data-year="'.$date->format('Y').'"]:visible\').click()');

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