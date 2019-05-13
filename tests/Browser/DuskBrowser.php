<?php

namespace Gogol\Admin\Tests\Browser;

use Gogol\Admin\Tests\Traits\AdminTrait;
use Laravel\Dusk\Browser;

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
     * Submit form
     */
    public function submitForm()
    {
        return $this->press(trans('admin::admin.send'));
    }

    /**
     * Check if success message exists
     * @return string
     */
    public function assertSeeSuccess($message)
    {
        $this->waitForText(trans('admin::admin.success-created'))
             ->assertSee(trans('admin::admin.success-created'));

         return $this;
    }
}