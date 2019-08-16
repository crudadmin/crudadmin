<?php

namespace Admin\Tests\Browser\Tests;

use Admin\Tests\App\Models\Single\SingleModel;
use Admin\Tests\App\Models\Single\SingleModelRelation;
use Admin\Tests\Browser\BrowserTestCase;
use Admin\Tests\Browser\DuskBrowser;
use Admin\Tests\Concerns\DropDatabase;

class ModelSingleTest extends BrowserTestCase
{
    use DropDatabase;

    /** @test */
    public function test_create_single_model_row()
    {
        $row = $this->getFormData();

        $this->browse(function (DuskBrowser $browser) use ($row) {
            $browser->openModelPage(SingleModel::class)

                    //Test create new single row
                    ->fillForm(SingleModel::class, $row)
                    ->submitForm()->assertSeeSuccess(trans('admin::admin.success-created'))->closeAlert()

                    //Check if form exists and has selected values
                    ->assertHasFormValues(SingleModel::class, $row)
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

    public function getFormData($key = null)
    {
        $data = [
            'name' => 'This is my string example value',
            'content' => 'This is my content example value',
        ];

        return isset($key) ? $data[$key] : $data;
    }
}
