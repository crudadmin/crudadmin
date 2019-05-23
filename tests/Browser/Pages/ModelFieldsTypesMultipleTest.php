<?php

namespace Gogol\Admin\Tests\Browser\Pages;

use Artisan;
use Carbon\Carbon;
use Gogol\Admin\Tests\App\Models\FieldsTypesMultiple;
use Gogol\Admin\Tests\Browser\BrowserTestCase;
use Gogol\Admin\Tests\Browser\DuskBrowser;
use Gogol\Admin\Tests\Traits\DropDatabase;
use Gogol\Admin\Tests\Traits\DropUploads;
use Illuminate\Foundation\Auth\User;

class ModelFieldsTypesMultipleTest extends BrowserTestCase
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
                    ->assertSeeLink('Fields types multiple')
                    ->clickLink('Fields types multiple')

                    //Check if form values has been successfully filled
                    ->fillForm(FieldsTypesMultiple::class, $row)
                    ->assertHasFormValues(FieldsTypesMultiple::class, $row)

                    //Check if form has been successfully saved
                    ->submitForm()
                    ->assertSeeSuccess(trans('admin::admin.success-created'))

                    //Check if form values has been successfully reseted
                    ->closeAlert()
                    ->assertFormIsEmpty(FieldsTypesMultiple::class);
        });

        $this->assertRowExists(FieldsTypesMultiple::class, $row);
    }

    /** @test */
    public function test_update_old_row()
    {
        $row = $this->getFormData();

        $this->browse(function (DuskBrowser $browser) use ($row) {
            $browser->loginAs(User::first())
                    ->visit(admin_action('DashboardController@index'))
                    ->clickLink('Fields types multiple')

                    //Create new row
                    ->fillForm(FieldsTypesMultiple::class, $row)
                    ->submitForm()
                    ->assertSeeSuccess(trans('admin::admin.success-created'))
                    ->closeAlert()
                    ->scrollToElement('body')

                    //Open row and check if has correct values
                    ->openRow(1)
                    ->assertHasFormValues(FieldsTypesMultiple::class, $row)

                    //Wait one second for proper component state and save existing row and check if has correct values
                    ->pause(1000)
                    ->saveForm()
                    ->assertSeeSuccess(trans('admin::admin.success-save'))
                    ->closeAlert()
                    ->assertHasFormValues(FieldsTypesMultiple::class, $row)

                    //Reset form after update and check for empty values
                    ->press(trans('admin::admin.new-row'))
                    ->assertFormIsEmpty(FieldsTypesMultiple::class);
        });

        $this->assertRowExists(FieldsTypesMultiple::class, $row);
    }

    public function getFormData($key = null)
    {
        $data = [
            'select' => 'option a',
            'select_multiple' => ['option c', 'option a'],
            'file' => 'image2.jpg',
            'file_multiple' => ['image1.jpg', 'image3.jpg'],
            'date' => date('d.m.Y'),
            'date_multiple' => [
                Carbon::now()->format('d.m.Y'),
                Carbon::now()->addDays(-1)->format('d.m.Y'),
            ],
            'datetime' => date('d.m.Y H:i'),
            'time' => date('H:i'),
            'time_multiple' => [ '00:30', '02:00', '12:00', '14:00', '17:30', '20:00', '21:30', '22:00' ],
        ];

        return isset($key) ? $data[$key] : $data;
    }
}