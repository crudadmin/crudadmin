<?php

namespace Admin\Tests\Browser\Tests;

use Carbon\Carbon;
use Admin\Tests\Browser\DuskBrowser;
use Admin\Tests\Concerns\DropUploads;
use Admin\Tests\Concerns\DropDatabase;
use Admin\Tests\Browser\BrowserTestCase;
use Admin\Tests\App\Models\Fields\FieldsType;

class ModelFieldsTypesTest extends BrowserTestCase
{
    use DropDatabase;
    use DropUploads;

    /** @test */
    public function test_fields_types_validation_errors_then_create_new_row_and_then_update_without_change()
    {
        $row = $this->getFormData();

        $this->browse(function (DuskBrowser $browser) use ($row) {
            $fieldKeys = array_keys((new FieldsType)->getFields());

            $browser->openModelPage(FieldsType::class)

                    //Check if validation of every field does work
                    ->assertDoesNotHaveValidationError(FieldsType::class, $fieldKeys)
                    ->submitForm()
                    ->assertHasValidationError(FieldsType::class, $fieldKeys)

                    //Check if custom component renders properly
                    ->assertSeeInFragment('[data-field="custom"] p', 'This is my first custom component for field my custom field, with empty value.')

                    //Check if form values has been successfully filled
                    ->fillForm(FieldsType::class, $row)
                    ->assertHasFormValues(FieldsType::class, $row)

                    //Check if custom component is modified properly
                    //Also check if row events has been triggered properly
                    ->assertSeeInFragment('[data-field="custom"] p', 'This is my first custom component for field my custom field, with my custom value value.')
                    ->assertSeeInFragment('[data-field="custom"] .custom-field-row-event', 'my custom value')
                    ->assertSeeInFragment('[data-field="custom"] .checkbox-field-row-event', 'true')

                    //Check if form has been successfully saved
                    ->submitForm()
                    ->assertSeeSuccess(trans('admin::admin.success-created'))

                    //Check if component values are reseted after new row has been created
                    ->assertSeeInFragment('[data-field="custom"] .custom-field-row-event', 'no value')
                    ->assertSeeInFragment('[data-field="custom"] .checkbox-field-row-event', 'no value')

                    //Check if form values has been successfully reseted after save and validation errors are gone
                    ->closeAlert()
                    ->assertDoesNotHaveValidationError(FieldsType::class, $fieldKeys)
                    ->assertFormIsEmpty(FieldsType::class)

                    //Check if table after creation contains of correct column values
                    ->assertTableRowExists(FieldsType::class, $this->getTableRow($row))

                    //Open row, update it, and check if still has same values after update without changing anything
                    ->openRow(1)
                    ->assertHasFormValues(FieldsType::class, $row)

                    //Check if row events has been triggered on new row open
                    ->assertSeeInFragment('[data-field="custom"] .custom-field-row-event', 'my custom value')
                    ->assertSeeInFragment('[data-field="custom"] .checkbox-field-row-event', 'true')

                    //Update row and check if has same values
                    ->fillForm(FieldsType::class, [
                        'password' => $this->getFormData('password'),
                    ])
                    ->saveForm()
                    ->assertSeeSuccess(trans('admin::admin.success-save'))
                    ->closeAlert()
                    ->assertHasFormValues(FieldsType::class, $row)
                    ->assertTableRowExists(FieldsType::class, $this->getTableRow($row));
        });

        $this->assertRowExists(FieldsType::class, $row);
    }

    /** @test */
    public function test_fields_types_update_existing_row()
    {
        $create = $this->getFormData();
        $update = $this->getFormDataUpdated();
        $rowUpdated = $this->createUpdatedRecord(FieldsType::class, $create, $update);

        //Create sample row
        FieldsType::create($this->buildDbData(FieldsType::class, $create));

        $this->browse(function (DuskBrowser $browser) use ($create, $update, $rowUpdated) {
            $browser->openModelPage(FieldsType::class)
                    //Open row and check if has correct values
                    ->openRow(1)
                    ->assertHasFormValues(FieldsType::class, $create)

                    //Update row and check if values has been properly changed
                    ->fillForm(FieldsType::class, $update)
                    ->assertHasFormValues(FieldsType::class, $rowUpdated)

                    //Save existing row and check if has correct values
                    ->saveForm()
                    ->assertSeeSuccess(trans('admin::admin.success-save'))
                    ->closeAlert()
                    ->assertHasFormValues(FieldsType::class, $rowUpdated)

                    //Reset form after update and check for empty values
                    ->press(trans('admin::admin.new-row'))
                    ->assertFormIsEmpty(FieldsType::class)

                    //Check if table contains of correct column values
                    ->pause(100)
                    ->assertTableRowExists(FieldsType::class, $this->getTableRow($rowUpdated));
        });

        $this->assertRowExists(FieldsType::class, $rowUpdated);
    }

    public function getFormData($key = null)
    {
        $data = [
            'string' => 'This is my string example value',
            'text' => 'This is my text example value',
            'editor' => '<p>This is my editor <strong>example</strong> value</p>',
            'select' => 'option a',
            'integer' => 10,
            'decimal' => 11.51,
            'file' => 'image1.jpg',
            'password' => 'password_test',
            'date' => date('d.m.Y'),
            'datetime' => date('d.m.Y H:00'),
            'time' => date('H:00'),
            'checkbox' => true,
            'radio' => 'b',
            'custom' => 'my custom value',
        ];

        return isset($key) ? $data[$key] : $data;
    }

    public function getFormDataUpdated()
    {
        return [
            'string' => 'This is my updated string example value',
            'text' => 'This is my updated text example value',
            'editor' => '<p>This is my updated editor <strong>example</strong> value</p>',
            'select' => 'option b',
            'integer' => '12',
            'decimal' => '14.20',
            'file' => 'image2.jpg',
            'password' => 'password_test',
            'date' => Carbon::now()->addDays(-1)->format('d.m.Y'),
            'datetime' => Carbon::now()->addDays(-1)->format('d.m.Y H:00'),
            'time' => Carbon::now()->addHours(-1)->format('H:00'),
            'checkbox' => false,
            'radio' => 'c',
            'custom' => 'updated custom value',
        ];
    }

    /*
     * Build array of correct values in table row
     */
    public function getTableRow($row)
    {
        $row = ['id' => '1'] + $row;

        unset($row['password']);
        unset($row['editor']);

        //Limit text with dots
        foreach ($row as $key => $value) {
            $row[$key] = str_limit($value, 20);
        }

        $row['file'] = trans('admin::admin.show-image');

        return $row;
    }
}
