<?php

namespace Gogol\Admin\Tests\Browser\Pages;

use Artisan;
use Carbon\Carbon;
use Gogol\Admin\Tests\App\Models\FieldsType;
use Gogol\Admin\Tests\Browser\BrowserTestCase;
use Gogol\Admin\Tests\Browser\DuskBrowser;
use Gogol\Admin\Tests\Traits\DropDatabase;
use Gogol\Admin\Tests\Traits\DropUploads;
use Illuminate\Foundation\Auth\User;

class ModelFieldsTypesTest extends BrowserTestCase
{
    use DropDatabase;
    use DropUploads;

    /** @test */
    public function test_create_new_row()
    {
        $row = $this->getFormData();

        $this->browse(function (DuskBrowser $browser) use ($row) {
            $browser->loginAs(User::first())
                    ->visit(admin_action('DashboardController@index'))
                    ->assertSeeLink('Fields types')
                    ->clickLink('Fields types')

                    //Check if form values has been successfully filled
                    ->fillForm(FieldsType::class, $row)
                    ->assertHasFormValues(FieldsType::class, $row)

                    //Check if form has been successfully saved
                    ->submitForm()
                    ->assertSeeSuccess(trans('admin::admin.success-created'))

                    //Check if form values has been successfully reseted
                    ->closeAlert()
                    ->assertFormIsEmpty(FieldsType::class);
        });

        $this->assertRowExists(FieldsType::class, $row);
    }

    /** @test */
    public function test_update_old_row()
    {
        $row = $this->getFormData();

        $this->browse(function (DuskBrowser $browser) use($row) {
            $browser->loginAs(User::first())
                    ->visit(admin_action('DashboardController@index'))
                    ->assertSeeLink('Fields types')
                    ->clickLink('Fields types')

                    //Create new row
                    ->fillForm(FieldsType::class, $row)
                    ->submitForm()
                    ->assertSeeSuccess(trans('admin::admin.success-created'))
                    ->closeAlert()
                    ->scrollToElement('body')

                    //Open row and check if has correct values
                    ->openRow(1)
                    ->assertHasFormValues(FieldsType::class, $row)
                    ->fillForm(FieldsType::class, [
                        'password' => $this->getFormData('password')
                    ])

                    //Save existing row and check if has correct values
                    ->saveForm()
                    ->assertHasFormValues(FieldsType::class, $row)
                    ->assertSeeSuccess(trans('admin::admin.success-save'))
                    ->closeAlert()

                    //Reset form after update and check for empty values
                    ->press(trans('admin::admin.new-row'))
                    ->assertFormIsEmpty(FieldsType::class);
        });

        $this->assertRowExists(FieldsType::class, $row);
    }

    public function getFormData($key = null)
    {
        $data = [
            'string' => 'This is my string example value',
            'text' => 'This is my text example value',
            'editor' => '<p>This is my editor <strong>example</strong> value</p>',
            'select' => 'option a',
            'integer' => 10,
            'decimal' => 11.5,
            'file' => 'image1.jpg',
            'password' => 'password_test',
            'date' => date('d.m.Y'),
            'datetime' => date('d.m.Y H:i'),
            'time' => date('H:i'),
            'checkbox' => true,
            'radio' => 'b',
        ];

        return isset($key) ? $data[$key] : $data;
    }
}