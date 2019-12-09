<?php

namespace Admin\Tests\Browser\Tests;

use Admin\Tests\App\Models\Single\SimpleModel;
use Admin\Tests\App\Models\Single\SingleModel;
use Admin\Tests\App\Models\Single\SingleModelGroupRelation;
use Admin\Tests\App\Models\Single\SingleModelRelation;
use Admin\Tests\Browser\BrowserTestCase;
use Admin\Tests\Browser\DuskBrowser;
use Admin\Tests\Concerns\DropDatabase;

class ModelSingleTest extends BrowserTestCase
{
    use DropDatabase;

    /** @test */
    public function test_create_single_model_row_with_inParent_row()
    {
        $row = $this->getFormData();
        $inParentRow = $this->getInParentFormData();

        $this->browse(function (DuskBrowser $browser) use ($row, $inParentRow) {
            $browser->openModelPage(SingleModel::class)

                    //Test create new single row
                    ->fillForm(SingleModel::class, $row)
                    ->submitForm()

                    //Check if single model relation $inParentMode has proper validation error in tab
                    ->waitFor('li[has-error]', 2)
                    ->assertHasAttribute('li:contains("inParent relation")', 'has-error')
                    ->clickLink('inParent relation')

                    //Check inParent validation errors
                    ->assertHasValidationError(SingleModelGroupRelation::class, ['name', 'date', 'file'])

                    //Fill inParent form
                    ->fillForm(SingleModelGroupRelation::class, $inParentRow)
                    ->submitForm()

                    ->assertSeeSuccess(trans('admin::admin.success-created'))->closeAlert()

                    //Check if form exists and has selected values
                    ->assertHasFormValues(SingleModel::class, $row + ['id' => 1])
                    ->assertHasFormValues(SingleModelGroupRelation::class, $inParentRow + ['id' => 1])
                    ->assertSeeIn('[data-tabs][data-model="single_model_relations"]', 'Single model relation (0)');
        });
    }

    /** @test */
    public function test_createSingleInParentRowInNonSingleModel()
    {
        $row = $this->getFormData();
        $inParentRow = $this->getInParentFormData();

        $this->browse(function (DuskBrowser $browser) use ($row, $inParentRow) {
            $inParentFieldKeys = array_keys((new SingleModelGroupRelation)->getFields());

            $browser->openModelPage(SimpleModel::class)

                    //Test create new single row
                    ->fillForm(SimpleModel::class, $row)
                    ->submitForm()

                    //Check if single model relation $inParentMode has proper validation error in tab
                    ->waitFor('li[has-error]', 2)
                    ->assertHasAttribute('li:contains("inParent relation")', 'has-error')
                    ->clickLink('inParent relation')

                    //Check inParent validation errors
                    ->assertHasValidationError(SingleModelGroupRelation::class, ['name', 'date', 'file'])

                    //Fill inParent form
                    ->fillForm(SingleModelGroupRelation::class, $inParentRow)
                    ->submitForm()
                    ->assertSeeSuccess(trans('admin::admin.success-created'))->closeAlert()

                    //Check if both forms has been successfully reseted
                    ->assertDoesNotHaveValidationError(SingleModelGroupRelation::class, $inParentFieldKeys)
                    ->assertFormIsEmpty(SimpleModel::class)
                    ->assertFormIsEmpty(SingleModelGroupRelation::class)

                    //Open added rows
                    ->openRow(1, SimpleModel::class)
                    ->waitUntilVue('row.name', 'This is my string example value', $browser->getModelBuilderSelector(SingleModelGroupRelation::class))
                    ->assertHasFormValues(SimpleModel::class, $row + ['id' => 1])
                    ->assertHasFormValues(SingleModelGroupRelation::class, $inParentRow + ['id' => 1])

                    ->saveForm()
                    ->assertDoesNotHaveValidationError(SingleModelGroupRelation::class, $inParentFieldKeys)
                    ->assertSeeSuccess(trans('admin::admin.success-save'))

                    //Check if simple model form has correct data
                    ->assertHasFormValues(SingleModelGroupRelation::class, $inParentRow + ['id' => 1]);
        });
    }

    /** @test */
    public function test_create_inParentOnExistingParentRow()
    {
        $row = $this->getFormData();
        $singleModelRow = SingleModel::create($row);

        $inParentRow = $this->getInParentFormData();

        $this->browse(function (DuskBrowser $browser) use ($row, $inParentRow) {
            $browser->openModelPage(SingleModel::class)
                    ->waitForText('Single model relation (0)')
                    ->clickLink('inParent relation')

                    //Fill and save inParent form
                    ->fillForm(SingleModelGroupRelation::class, $inParentRow)
                    ->saveForm()
                    ->assertSeeSuccess(trans('admin::admin.success-save'))->closeAlert()

                    //Check if form exists and has selected values
                    ->assertHasFormValues(SingleModel::class, $row + ['id' => 1])
                    ->assertHasFormValues(SingleModelGroupRelation::class, $inParentRow + ['id' => 1])
                    ->assertSeeIn('[data-tabs][data-model="single_model_relations"]', 'Single model relation (0)');
        });
    }

    /** @test */
    public function test_loaded_single_model_row()
    {
        $row = $this->getFormData();
        $relationRow = ['name' => 'single relation data', 'content' => 'relation content'];
        $singleModelRow = SingleModel::create($row);

        SingleModelRelation::create(['single_model_id' => $singleModelRow->getKey() ] + $relationRow);

        $this->browse(function (DuskBrowser $browser) use ($row, $relationRow) {
            $browser->openModelPage(SingleModel::class)

                    //Check if single model is automaticaly loaded from database
                    ->assertHasFormValues(SingleModel::class, $row)
                    ->assertSeeIn('[data-tabs][data-model="single_model_relations"]', 'Single model relation (1)')

                    //Open single model relation, and check if is loaded
                    ->click('[data-tabs][data-model="single_model_relations"]')
                    ->assertHasFormValues(SingleModelRelation::class, $relationRow);

        });
    }

    public function getInParentFormData($key = null)
    {
        $data = [
            'name' => 'This is my string example value',
            'file' => 'image1.jpg',
            'date' => date('d.m.Y'),
        ];

        return isset($key) ? $data[$key] : $data;
    }

    public function getFormData($key = null)
    {
        $data = [
            'name' => 'This is my string example value',
            'content' => 'This is my content example value',
        ];

        return isset($key) ? $data[$key] : $data;
    }
}
