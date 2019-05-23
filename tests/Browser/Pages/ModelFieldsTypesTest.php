<?php

namespace Gogol\Admin\Tests\Browser\Pages;

use Carbon\Carbon;
use Gogol\Admin\Tests\App\Models\FieldsType;
use Gogol\Admin\Tests\Browser\BrowserTestCase;
use Gogol\Admin\Tests\Browser\DuskBrowser;
use Gogol\Admin\Tests\Traits\DropDatabase;
use Gogol\Admin\Tests\Traits\DropUploads;

class ModelFieldsTypesTest extends BrowserTestCase
{
    use DropDatabase;
    use DropUploads;

    /** @test */
    public function test_validation_errors_then_create_new_row_and_then_update_without_change()
    {
        $row = $this->getFormData();

        $this->browse(function (DuskBrowser $browser) use ($row) {
            $fieldKeys = array_keys((new FieldsType)->getFields());

            $browser->openModelPage(FieldsType::class)

                    //Check if validation does work
                    ->assertDoesNotHaveValidationError(FieldsType::class, $fieldKeys)
                    ->submitForm()
                    ->pause(500)
                    ->assertHasValidationError(FieldsType::class, $fieldKeys)

                    //Check if form values has been successfully filled
                    ->fillForm(FieldsType::class, $row)
                    ->assertHasFormValues(FieldsType::class, $row)

                    //Check if form has been successfully saved
                    ->submitForm()
                    ->assertSeeSuccess(trans('admin::admin.success-created'))

                    //Check if form values has been successfully reseted after save and validation errors are gone
                    ->closeAlert()
                    ->assertDoesNotHaveValidationError(FieldsType::class, $fieldKeys)
                    ->assertFormIsEmpty(FieldsType::class)

                    //Check if table after creation contains of correct column values
                    ->assertTableRowExists(FieldsType::class, $this->getTableRow($row))

                    //Open row, update it, and check if still has same values after update without changing anything
                    ->openRow(1)
                    ->assertHasFormValues(FieldsType::class, $row)
                    ->fillForm(FieldsType::class, [
                        'password' => $this->getFormData('password')
                    ])
                    ->pause(1000)
                    ->saveForm()
                    ->assertSeeSuccess(trans('admin::admin.success-save'))
                    ->closeAlert()
                    ->assertHasFormValues(FieldsType::class, $row)
                    ->assertTableRowExists(FieldsType::class, $this->getTableRow($row));
        });

        $this->assertRowExists(FieldsType::class, $row);
    }

    /** @test */
    public function test_update_existing_row()
    {
        $create = $this->getFormData();
        $update = $this->getFormDataUpdated();
        $rowUpdated = $this->createUpdatedRecord($create, $update);

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
            'integer' => '10',
            'decimal' => '11.50',
            'file' => 'image1.jpg',
            'password' => 'password_test',
            'date' => date('d.m.Y'),
            'datetime' => date('d.m.Y H:00'),
            'time' => date('H:00'),
            'checkbox' => true,
            'radio' => 'b',
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
        foreach ($row as $key => $value)
            $row[$key] = str_limit($value, 20);

        $row['file'] = trans('admin::admin.show-image');

        return $row;
    }
}