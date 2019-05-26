<?php

namespace Gogol\Admin\Tests\Browser\Pages;

use Carbon\Carbon;
use Gogol\Admin\Tests\App\Models\FieldsTypesMultiple;
use Gogol\Admin\Tests\Browser\BrowserTestCase;
use Gogol\Admin\Tests\Browser\DuskBrowser;
use Gogol\Admin\Tests\Traits\DropDatabase;
use Gogol\Admin\Tests\Traits\DropUploads;

class ModelFieldsTypesMultipleTest extends BrowserTestCase
{
    use DropDatabase;
    use DropUploads;

    /** @test */
    public function test_validation_errors_then_create_new_row_and_then_update_without_change()
    {
        $row = $this->getFormData();

        $this->browse(function (DuskBrowser $browser) use ($row) {
            $fieldKeys = array_keys((new FieldsTypesMultiple)->getFields());

            $browser->openModelPage(FieldsTypesMultiple::class)

                    //Check if validation of every field does work
                    ->assertDoesNotHaveValidationError(FieldsTypesMultiple::class, $fieldKeys)
                    ->submitForm()
                    ->pause(500)
                    ->assertHasValidationError(FieldsTypesMultiple::class, $fieldKeys)

                    //Check if form values has been successfully filled
                    ->fillForm(FieldsTypesMultiple::class, $row)
                    ->assertHasFormValues(FieldsTypesMultiple::class, $row)

                    //Check if form has been successfully saved
                    ->submitForm()
                    ->assertSeeSuccess(trans('admin::admin.success-created'))

                    //Check if form values has been successfully reseted after save
                    ->closeAlert()
                    ->assertDoesNotHaveValidationError(FieldsTypesMultiple::class, $fieldKeys)
                    ->assertFormIsEmpty(FieldsTypesMultiple::class)

                    //Check if table after creation contains of correct column values
                    ->assertTableRowExists(FieldsTypesMultiple::class, $this->getTableRow($row))

                    //Open row, update it, and check if still has same values after update without changing anything
                    ->openRow(1)
                    ->assertHasFormValues(FieldsTypesMultiple::class, $row)
                    ->pause(1000)
                    ->saveForm()
                    ->assertSeeSuccess(trans('admin::admin.success-save'))
                    ->closeAlert()
                    ->assertHasFormValues(FieldsTypesMultiple::class, $row)
                    ->assertTableRowExists(FieldsTypesMultiple::class, $this->getTableRow($row));
        });

        $this->assertRowExists(FieldsTypesMultiple::class, $row);
    }

    /** @test */
    public function test_update_existing_row()
    {
        $create = $this->getFormData();
        $update = $this->getFormDataUpdated();
        $rowUpdated = $this->createUpdatedRecord(FieldsTypesMultiple::class, $create, $update);

        //Create sample row
        FieldsTypesMultiple::create($this->buildDbData(FieldsTypesMultiple::class, $create));

        $this->browse(function (DuskBrowser $browser) use ($create, $update, $rowUpdated) {
            $browser->openModelPage(FieldsTypesMultiple::class)
                    //Open row and check if has correct values
                    ->openRow(1)
                    ->assertHasFormValues(FieldsTypesMultiple::class, $create)

                    //Update row and check if values has been properly changed
                    ->fillForm(FieldsTypesMultiple::class, $update)
                    ->assertHasFormValues(FieldsTypesMultiple::class, $rowUpdated)

                    //Wait one second for proper component state and save updated row and check if has correct values
                    ->saveForm()
                    ->assertSeeSuccess(trans('admin::admin.success-save'))
                    ->closeAlert()
                    ->assertHasFormValues(FieldsTypesMultiple::class, $rowUpdated)

                    //Reset form after update and check for empty values
                    ->press(trans('admin::admin.new-row'))
                    ->assertFormIsEmpty(FieldsTypesMultiple::class)

                    //Check if table contains of correct column values
                    ->pause(100)
                    ->assertTableRowExists(FieldsTypesMultiple::class, $this->getTableRow($rowUpdated));
        });

        $this->assertRowExists(FieldsTypesMultiple::class, $rowUpdated);
    }

    public function getFormData()
    {
        return [
            'select' => 'option a',
            'select_multiple' => ['option c', 'option a'],
            'file' => 'image2.jpg',
            'file_multiple' => ['image1.jpg', 'image3.jpg'],
            'date' => date('d.m.Y'),
            'date_multiple' => [
                Carbon::now()->format('d.m.Y'),
                Carbon::now()->addDays(-1)->format('d.m.Y'),
            ],
            'datetime' => date('d.m.Y H:00'),
            'time' => date('H:00'),
            'time_multiple' => [ '00:30', '02:00', '12:00', '14:00', '17:30', '20:00', '21:30', '22:00' ],
        ];
    }

    public function getFormDataUpdated()
    {
        return [
            'select' => 'option b',
            'select_multiple' => ['option b'],
            'file_multiple' => ['image2.jpg'],
            'date' => Carbon::now()->addDays(-1)->format('d.m.Y'),
            'date_multiple' => [
                Carbon::now()->addDays(-2)->format('d.m.Y'),
            ],
            'datetime' => Carbon::now()->addDays(-1)->format('d.m.Y H:00'),
            'time' => Carbon::now()->addHours(-1)->format('H:00'),
            'time_multiple' => [ '16:00' ],
        ];
    }

    /*
     * Build array of correct values in table row
     */
    public function getTableRow($original)
    {
        $row = ['id' => '1'] + $original;

        //Limit text with dots
        foreach ($original as $key => $value)
        {
            //Join all array values with dots
            if ( is_array($value) )
                $row[$key] = implode(', ', $value);

            //Set limit of strings
            if ( is_string($row[$key]) )
                $row[$key] = $this->strLimit($row[$key], 20);
        }

        //Overide file values with clickable texts
        $row['file'] = trans('admin::admin.show-image');
        $row['file_multiple'] = implode(' , ', array_map(function($item){
            return trans('admin::admin.show-image');
        }, $original['file_multiple']));

        return $row;
    }
}