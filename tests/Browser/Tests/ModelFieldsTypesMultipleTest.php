<?php

namespace Admin\Tests\Browser\Tests;

use Carbon\Carbon;
use Admin\Tests\Browser\DuskBrowser;
use Admin\Tests\Concerns\DropUploads;
use Admin\Tests\Concerns\DropDatabase;
use Admin\Tests\Browser\BrowserTestCase;
use Admin\Tests\App\Models\Fields\FieldsTypesMultiple;

class ModelFieldsTypesMultipleTest extends BrowserTestCase
{
    use DropDatabase;
    use DropUploads;

    /** @test */
    public function test_fields_types_multiple_validation_errors_then_create_new_row_and_then_update_without_change()
    {
        $row = $this->getFormData();

        $this->browse(function (DuskBrowser $browser) use ($row) {
            $fieldKeys = array_keys((new FieldsTypesMultiple)->getFields());

            $browser->openModelPage(FieldsTypesMultiple::class)
                    ->openForm()
                    //Check if validation of every field does work
                    ->assertDoesNotHaveValidationError(FieldsTypesMultiple::class, $fieldKeys)
                    ->submitForm()
                    ->assertHasValidationError(FieldsTypesMultiple::class, $fieldKeys)

                    //Check if form values has been successfully filled
                    ->fillForm(FieldsTypesMultiple::class, $row)
                    ->assertHasFormValues(FieldsTypesMultiple::class, $row)

                    //Check if form has been successfully saved
                    ->submitForm()
                    ->assertSeeSuccess(trans('admin::admin.success-created'))

                    //Check if form values has been successfully reseted after save
                    ->closeAlert()
                    ->openForm()
                    ->assertDoesNotHaveValidationError(FieldsTypesMultiple::class, $fieldKeys)
                    ->assertFormIsEmpty(FieldsTypesMultiple::class)
                    ->closeForm()

                    //Check if table after creation contains of correct column values
                    ->assertTableRowExists(FieldsTypesMultiple::class, $this->getTableRow($row))

                    //Open row, update it, and check if still has same values after update without changing anything
                    ->openRow(1)
                    ->assertHasFormValues(FieldsTypesMultiple::class, $row)
                    ->saveForm()
                    ->assertSeeSuccess(trans('admin::admin.success-save'))
                    ->closeAlert()
                    ->assertHasFormValues(FieldsTypesMultiple::class, $row)
                    ->closeForm()
                    ->assertTableRowExists(FieldsTypesMultiple::class, $this->getTableRow($row));
        });

        $this->assertRowExists(FieldsTypesMultiple::class, $row);
    }

    private function getRowWithOneRemovedFile($row)
    {
        unset($row['file_multiple'][1]);
        $row['file_multiple'] = array_values($row['file_multiple']);

        return $row;
    }

    /** @test */
    public function test_fields_types_multiple_update_existing_row()
    {
        $create = $this->getFormData();
        $update = $this->getFormDataUpdated();
        $rowUpdated = $this->createUpdatedRecord(FieldsTypesMultiple::class, $create, $update);
        $rowWithoutFile = $this->getRowWithOneRemovedFile($rowUpdated);

        //Create sample row
        FieldsTypesMultiple::create($this->buildDbData(FieldsTypesMultiple::class, $create));

        $this->browse(function (DuskBrowser $browser) use ($create, $update, $rowUpdated, $rowWithoutFile) {
            $browser->openModelPage(FieldsTypesMultiple::class)
                    //Open row and check if has correct values
                    ->openRow(1)
                    ->assertHasFormValues(FieldsTypesMultiple::class, $create)

                    //Update row and check if values has been properly changed
                    ->fillForm(FieldsTypesMultiple::class, $update)
                    ->assertHasFormValues(FieldsTypesMultiple::class, $rowUpdated)

                    //Wait one second for proper component state and save updated row and check if has correct values
                    ->saveForm()->assertSeeSuccess(trans('admin::admin.success-save'))->closeAlert()
                    ->assertHasFormValues(FieldsTypesMultiple::class, $rowUpdated)

                    //Remove one item from file upload, and save form 2 times, because if something happens
                    //with missing file, it will be obvious after 2 form saves.
                    ->jsClick('[data-field="file_multiple"] .chosen-choices li:contains(image3.jpg) a')->pause(400)
                    ->saveForm()->assertSeeSuccess(trans('admin::admin.success-save'))->closeAlert()->pause(400)
                    ->saveForm()->assertSeeSuccess(trans('admin::admin.success-save'))->closeAlert()
                    ->assertHasFormValues(FieldsTypesMultiple::class, $rowWithoutFile, null, true)

                    //Reset form after update and check for empty values
                    ->press(trans('admin::admin.new-row'))
                    ->assertFormIsEmpty(FieldsTypesMultiple::class)

                    //Check if table contains of correct column values
                    ->pause(100)
                    ->assertTableRowExists(FieldsTypesMultiple::class, $this->getTableRow($rowWithoutFile));
        });

        $this->assertRowExists(FieldsTypesMultiple::class, $rowWithoutFile);
    }

    public function getFormData()
    {
        return [
            'select_multiple' => ['option c', 'option a'],
            'file_multiple' => ['image1.jpg', 'image3.jpg'],
            'date_multiple' => [
                Carbon::now()->format('d.m.Y'),
                Carbon::now()->addDays(-1)->format('d.m.Y'),
            ],
            'time_multiple' => ['00:30', '02:00', '12:00', '14:00', '17:30', '20:00', '21:30', '22:00'],
        ];
    }

    public function getFormDataUpdated()
    {
        return [
            'select_multiple' => ['option b'],
            'file_multiple' => ['image2.jpg'],
            'date_multiple' => [
                Carbon::now()->addDays(-2)->format('d.m.Y'),
            ],
            'time_multiple' => ['16:00'],
        ];
    }

    /*
     * Build array of correct values in table row
     */
    public function getTableRow($original)
    {
        $row = ['id' => '1'] + $original;

        //Limit text with dots
        foreach ($original as $key => $value) {
            //Join all array values with dots
            if (is_array($value)) {
                $row[$key] = implode(', ', $value);
            }

            //Set limit of strings
            if (is_string($row[$key])) {
                $row[$key] = $this->strLimit($row[$key], 20);
            }
        }

        //Overide file values with clickable texts
        $row['file_multiple'] = implode(' , ', array_map(function ($item) {
            return trans('admin::admin.show-image');
        }, $original['file_multiple']));

        return $row;
    }
}
