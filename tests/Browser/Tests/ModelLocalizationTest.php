<?php

namespace Admin\Tests\Browser\Tests;

use Admin\Tests\Browser\DuskBrowser;
use Admin\Tests\Concerns\DropUploads;
use Admin\Tests\Concerns\DropDatabase;
use Admin\Tests\Browser\BrowserTestCase;
use Admin\Tests\App\Models\Locales\ModelLocale;
use Admin\Tests\App\Models\Locales\ModelLocalization;

class ModelLocalizationTest extends BrowserTestCase
{
    use DropDatabase,
        DropUploads;

    /** @test */
    public function test_localization_rows()
    {
        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(ModelLocalization::class)
                    ->openForm()
                    //Check if row has been successfully added
                    ->fillForm(ModelLocalization::class, [
                        'name' => 'sk name',
                    ])->submitForm()->assertSeeSuccess(trans('admin::admin.success-created'))->closeAlert()
                    ->assertColumnRowData(ModelLocalization::class, 'name', ['sk name'])
                    //Change into english language and check if table is empty
                    ->jsClick('[data-global-language-switch]')
                    ->jsClick('[data-global-language-switch] li:nth-child(2)')

                    ->waitUntilMissing('[data-id="1"]');
            $this->assertEquals([], $browser->getRows(ModelLocalization::class));

            //Add two rows into english language and check correct values
            $browser->openForm()
                    ->fillForm(ModelLocalization::class, [
                        'name' => 'en name',
                    ])->submitForm()->assertSeeSuccess(trans('admin::admin.success-created'))->closeAlert()
                    ->assertColumnRowData(ModelLocalization::class, 'name', ['en name'])
                    ->fillForm(ModelLocalization::class, [
                        'name' => 'en name second',
                    ])->submitForm()->assertSeeSuccess(trans('admin::admin.success-created'))->closeAlert()
                    ->assertColumnRowData(ModelLocalization::class, 'name', ['en name second', 'en name']);

            //Change back to slovak language, and check correct rows
            $browser->jsClick('[data-global-language-switch]')
                    ->jsClick('[data-global-language-switch] li:nth-child(1)')
                    ->waitFor('[data-id="1"]')
                    ->assertColumnRowData(ModelLocalization::class, 'name', ['sk name']);
        });
    }

    /** @test */
    public function test_locales_create_and_update_row()
    {
        $row_sk = $this->getFormDataSK();
        $row_en = $this->getFormDataEN();

        $this->browse(function (DuskBrowser $browser) use ($row_sk, $row_en) {
            $browser->openModelPage(ModelLocale::class)
                    ->openForm()
                    //Check if form values has been successfully filled
                    ->fillForm(ModelLocale::class, $row_sk, 'sk')
                    ->assertHasFormValues(ModelLocale::class, $row_sk, 'sk')

                    //Check if row has been successfully saved
                    ->submitForm()
                    ->assertSeeSuccess(trans('admin::admin.success-created'))
                    ->closeAlert()
                    ->openForm()

                    //Check if form values has been successfully reseted after save
                    ->assertFormIsEmpty(ModelLocale::class, 'sk')->closeForm()

                    //Check if table after creation contains of correct column values
                    ->assertTableRowExists(ModelLocale::class, $this->getTableRow($row_sk))

                    //Open row and check if english is empty
                    ->openRow(1)
                    ->changeRowLanguage('en')
                    ->assertFormIsEmpty(ModelLocale::class, 'en')

                    //Fill and save english form values
                    ->fillForm(ModelLocale::class, $row_en, 'en')
                    ->saveForm()
                    ->assertSeeSuccess(trans('admin::admin.success-save'))
                    ->closeAlert()

                    //Check correct valeus
                    ->assertHasFormValues(ModelLocale::class, $row_sk, 'sk')
                    ->assertHasFormValues(ModelLocale::class, $row_en, 'en')
                    ->closeForm()
                    ->assertTableRowExists(ModelLocale::class, $this->getTableRow($row_sk));
        });

        $this->assertRowExists(ModelLocale::class, $this->createLangArray($row_sk, $row_en));
    }

    /** @test */
    public function test_locales_default_language_validation_error()
    {
        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(ModelLocale::class)

                    //Submit form and check if language switched is not colorized
                    ->openForm()
                    ->submitForm()
                    ->assertHasNotClass('[data-form-language-switch] > button', 'has-error')

                    //Change language to english, and again send form, and chech if language switcher is colorized
                    ->changeRowLanguage('en')
                    ->submitForm()->pause(100)
                    ->assertSeeIn('.modal', trans('admin::admin.lang-error'))
                    ->closeAlert()
                    ->assertHasClass('[data-form-language-switch] > button', 'has-error');
        });
    }

    /*
     * Merge two simple arrays and set language keys into each value
     */
    private function createLangArray($row_sk, $row_en)
    {
        $data = [];

        foreach ($row_sk as $key => $value) {
            $data[$key]['sk'] = $value;
            $data[$key]['en'] = $row_en[$key];
        }

        return $data;
    }

    public function getFormDataSK($key = null)
    {
        $data = [
            'string' => 'This is my string example value',
            'text' => 'This is my text example value',
            'select' => 'option a',
            'integer' => '10',
            'decimal' => '11.50',
            'file' => 'image1.jpg',
            'date' => date('d.m.Y'),
            'datetime' => date('d.m.Y H:00'),
            'time' => date('H:00'),
            'checkbox' => true,
            'radio' => 'b',
            'editor' => '<p>This is my editor <strong>example</strong> value</p>',
        ];

        return isset($key) ? $data[$key] : $data;
    }

    public function getFormDataEN($key = null)
    {
        $data = [
            'string' => 'english string example value',
            'text' => 'en example value',
            'editor' => '<p>en editor <strong>example</strong> value</p>',
            'select' => 'option b',
            'integer' => '12',
            'decimal' => '15.10',
            'file' => 'image2.jpg',
            'date' => now()->addDays(-1)->format('d.m.Y'),
            'datetime' => now()->addDays(-1)->format('d.m.Y H:00'),
            'time' => date('12:00'),
            'checkbox' => false,
            'radio' => 'c',
        ];

        return isset($key) ? $data[$key] : $data;
    }

    /*
     * Build array of correct values in table row
     */
    public function getTableRow($row, $id = 1)
    {
        $row = ['id' => ''.$id] + $row;

        unset($row['editor']);

        //Limit text with dots
        foreach ($row as $key => $value) {
            $row[$key] = str_limit($value, 20);
        }

        $row['file'] = trans('admin::admin.show-image');

        return $row;
    }
}
