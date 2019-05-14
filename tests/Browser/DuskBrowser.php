<?php

namespace Gogol\Admin\Tests\Browser;

use Gogol\Admin\Tests\Traits\AdminTrait;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Assert as PHPUnit;

class DuskBrowser extends Browser
{
    use AdminTrait;

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
            if ( $model->isFieldType($key, ['string', 'longtext', 'text', 'integer', 'decimal', 'password', 'date', 'datetime', 'time']) )
                $this->type($key, $value);

            //Set editor value
            else if ( $model->isFieldType($key, ['longeditor', 'editor']) )
                $this->with('textarea[name='.$key.']', function($browser) use ($key, $value) {
                    $browser->script('CKEDITOR.instances[$("[name='.$key.']").attr("id")].setData("'.$value.'")');
                });

            //Set select value
            else if ( $model->isFieldType($key, ['select']) )
                $this->value('select[name="'.$key.'"]', $value)
                     ->with('select[name='.$key.']', function($browser) use ($key) {
                         $browser->script('$("select[name='.$key.']").change().trigger("chosen:updated");');
                     });

            //Set file value
            else if ( $model->isFieldType($key, ['file']) )
                $this->attach($key, $this->getStubPath('/uploads/'.$value));

            //Set file value
            else if ( $model->isFieldType($key, ['checkbox']) )
                $this->{$value === true || $value === 1 ? 'check' : 'uncheck'}($key);

            //Set file value
            else if ( $model->isFieldType($key, ['radio']) )
                $this->radio($key, $value);
        }

        return $this;
    }

    /**
     * Check if form has filled form values
     * @param  class $model
     * @param  array  $array
     * @return object
     */
    public function assertHasFormValues($model, $array = [])
    {
        foreach ($array as $key => $value)
        {
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
    public function assertHasClass($element, $class)
    {
        $classes = explode(' ', $this->attribute($element, 'class'));

        PHPUnit::assertContains(
            $class, $classes,
            'Class ['.$class.'] does not exists in element ['.$element.']'
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
}