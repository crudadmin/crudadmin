<?php

namespace Gogol\Admin\Tests\Browser\Pages;

use Carbon\Carbon;
use Gogol\Admin\Tests\App\Models\FieldsRelation;
use Gogol\Admin\Tests\Browser\BrowserTestCase;
use Gogol\Admin\Tests\Browser\DuskBrowser;
use Gogol\Admin\Tests\Browser\Traits\SeedTrait;
use Gogol\Admin\Tests\Traits\DropDatabase;
use Illuminate\Support\Facades\DB;

class ModelFieldsTypesRelationsTest extends BrowserTestCase
{
    use DropDatabase,
        SeedTrait;

    /** @test */
    public function test_validation_errors_then_create_new_row_and_then_update_without_change()
    {
        $this->createArticleMoviesList();

        $row = $this->getFormData();

        $this->browse(function (DuskBrowser $browser) use ($row) {
            $fieldKeys = array_keys((new FieldsRelation)->getFields());

            $browser->openModelPage(FieldsRelation::class)

                    //Check if validation of every field does work
                    ->assertDoesNotHaveValidationError(FieldsRelation::class, $fieldKeys)
                    ->submitForm()
                    ->pause(500)
                    ->assertHasValidationError(FieldsRelation::class, $fieldKeys)

                    //Check if form values has been successfully filled
                    ->fillForm(FieldsRelation::class, $row)
                    ->assertHasFormValues(FieldsRelation::class, $row)

                    //Check if form has been successfully saved
                    ->submitForm()
                    ->assertSeeSuccess(trans('admin::admin.success-created'))

                    //Check if form values has been successfully reseted after save and validation errors are gone
                    ->closeAlert()
                    ->assertDoesNotHaveValidationError(FieldsRelation::class, $fieldKeys)
                    ->assertFormIsEmpty(FieldsRelation::class)

                    //Check if table after creation contains of correct column values
                    ->assertTableRowExists(FieldsRelation::class, $this->getTableRow($row))

                    //Open row, update it, and check if still has same values after update without changing anything
                    ->openRow(1)
                    ->assertHasFormValues(FieldsRelation::class, $row)
                    ->pause(1000)
                    ->saveForm()
                    ->assertSeeSuccess(trans('admin::admin.success-save'))
                    ->closeAlert()
                    ->assertHasFormValues(FieldsRelation::class, $row)
                    ->assertTableRowExists(FieldsRelation::class, $this->getTableRow($row));
        });

        $this->assertRowExists(FieldsRelation::class, $row, 1);
    }

    /** @test */
    public function test_update_existing_row()
    {
        $create = $this->getFormData();
        $update = $this->getFormDataUpdated();
        $rowUpdated = $this->createUpdatedRecord(FieldsRelation::class, $create, $update);

        $this->createArticleMoviesList();

        //Create sample row
        FieldsRelation::create($this->buildDbData(FieldsRelation::class, $create));
        $this->saveFieldRelationsValues(FieldsRelation::class, $create);

        $this->browse(function (DuskBrowser $browser) use ($create, $update, $rowUpdated) {
            $browser->openModelPage(FieldsRelation::class)
                    //Open row and check if has correct values
                    ->openRow(1)
                    ->assertHasFormValues(FieldsRelation::class, $create)

                    //Update row and check if values has been properly changed
                    ->fillForm(FieldsRelation::class, $update)
                    ->assertHasFormValues(FieldsRelation::class, $rowUpdated)

                    //Save existing row and check if has correct values
                    ->saveForm()
                    ->assertSeeSuccess(trans('admin::admin.success-save'))
                    ->closeAlert()
                    ->assertHasFormValues(FieldsRelation::class, $rowUpdated)

                    //Reset form after update and check for empty values
                    ->press(trans('admin::admin.new-row'))
                    ->assertFormIsEmpty(FieldsRelation::class)

                    //Check if table contains of correct column values
                    ->pause(100)
                    ->assertTableRowExists(FieldsRelation::class, $this->getTableRow($rowUpdated));
        });

        $this->assertRowExists(FieldsRelation::class, $rowUpdated, 1);
    }

    public function getFormData($key = null)
    {
        return $this->getFieldsRelationFormData();
    }

    public function getFormDataUpdated()
    {
        return [
            'relation1_id' => [4 => 'captain marvel'],
            'relation2_id' => [5 => 'my option aquaman 4'],
            'relation3_id' => [11 => 'my second option superman 20'],
            'relation_multiple1' => [ ],
            'relation_multiple2' => [ 9 => 'my option hellboy 8' ], //we want remove this item
            'relation_multiple3' => [ 11 => 'second option superman 20', 10 => 'second option spider-man 18' ],
        ];
    }

    /*
     * Build array of correct values in table row
     */
    public function getTableRow($row)
    {
        $row = ['id' => '1'] + $row;

        unset($row['relation_multiple1']);
        unset($row['relation_multiple2']);
        unset($row['relation_multiple3']);

        return $row;
    }
}