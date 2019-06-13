<?php

namespace Gogol\Admin\Tests\Browser\Pages;

use Gogol\Admin\Tests\App\Models\ModelLocalization;
use Gogol\Admin\Tests\Browser\BrowserTestCase;
use Gogol\Admin\Tests\Browser\DuskBrowser;
use Gogol\Admin\Tests\Traits\DropDatabase;

class ModelLocalizationTest extends BrowserTestCase
{
    use DropDatabase;

    /** @test */
    public function test_localization_rows()
    {
        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(ModelLocalization::class)

                    //Check if row has been successfully added
                    ->fillForm(ModelLocalization::class, [
                        'name' => 'sk name',
                    ])->submitForm()->assertSeeSuccess(trans('admin::admin.success-created'))->closeAlert()
                    ->assertColumnRowData(ModelLocalization::class, 'name', [ 'sk name' ])

                    //Change into english language and check if table is empty
                    ->valueWithEvent('[data-global-language-switch]', 2, 'change')->pause(300);
                    $this->assertEquals([], $browser->getRows(ModelLocalization::class));

            //Add two rows into english language and check correct values
            $browser->fillForm(ModelLocalization::class, [
                        'name' => 'en name',
                    ])->submitForm()->assertSeeSuccess(trans('admin::admin.success-created'))->closeAlert()
                    ->assertColumnRowData(ModelLocalization::class, 'name', [ 'en name' ])
                    ->fillForm(ModelLocalization::class, [
                        'name' => 'en name second',
                    ])->submitForm()->assertSeeSuccess(trans('admin::admin.success-created'))->closeAlert()
                    ->assertColumnRowData(ModelLocalization::class, 'name', [ 'en name second', 'en name' ]);

            //Change back to slovak language, and check correct rows
            $browser->valueWithEvent('[data-global-language-switch]', 1, 'change')->pause(300)
                    ->assertColumnRowData(ModelLocalization::class, 'name', [ 'sk name' ]);
        });
    }
}