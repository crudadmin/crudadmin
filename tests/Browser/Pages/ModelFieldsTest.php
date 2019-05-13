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

class ModelFieldsTest extends BrowserTestCase
{
    use DropDatabase;
    use DropUploads;

    /** @test */
    public function test_fields_interactivity()
    {
        $this->browse(function (DuskBrowser $browser) {
            $browser->loginAs(User::first())
                    ->visit(admin_action('DashboardController@index'))
                    ->assertSeeLink('Fields types')
                    ->clickLink('Fields types')

                    ->fillForm(FieldsType::class, $this->getFormData())
                    ->assertHasFormValues(FieldsType::class, array_diff_key($this->getFormData(), array_flip(['editor', 'file'])))

                    ->submitForm()
                    ->assertSeeSuccess(trans('admin::admin.success-created'));
        });

        $this->assertRowExists(FieldsType::class, [
            'date' => Carbon::createFromFormat('d.m.Y', $this->getFormData('date'))->format('Y-m-d'),
            'datetime' => Carbon::createFromFormat('d.m.Y H:i', $this->getFormData('datetime'))->format('Y-m-d H:i:s'),
            'time' => $this->getFormData('time'),
        ] + $this->getFormData());
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