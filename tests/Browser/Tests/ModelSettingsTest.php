<?php

namespace Admin\Tests\Browser\Tests;

use Admin\Tests\Browser\DuskBrowser;
use Admin\Tests\Concerns\DropDatabase;
use Admin\Tests\App\Models\Tree\Model1;
use Admin\Tests\Browser\BrowserTestCase;

class ModelSettingsTest extends BrowserTestCase
{
    use DropDatabase;

    /** @test */
    public function test_model_titles_and_buttons_texts()
    {
        Model1::create([
            'field1' => 'test item',
            'field2' => '<a href="#">Text</a>',
            'field3' => '<a href="#">Text</a>',
            'field4' => 'column 4 longest text more than 20 chars...',
        ]);

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Model1::class)

                    //Check text of buttons
                    ->assertSeeIn('[data-header]', 'Hlavička nového záznamu')
                    ->assertSeeIn('[data-footer]', 'Odoslať nový záznam')
                    ->openRow(1)
                    ->assertSeeIn('[data-header]', 'Vytvoriť nový záznam')
                    ->assertSeeIn('[data-header]', 'Upravujete záznam č. 1, test item')
                    ->assertSeeIn('[data-footer]', 'Upraviť starý záznam')

                    //Test limits, encode an column order settings
                    ->assertTableRowExists(Model1::class, [
                        'id' => 1,
                        'field3' => 'Text',
                        'field1' => 'test ...',
                        'field4' => 'column 4 longest tex...',
                        'field2' => '<a href="#">Text</a>',
                        'field5' => 'my non existing colu...',
                    ])
                    ->assertSeeIn('[data-table-rows] thead', 'TEST FIELD')
                    ->assertSeeIn('[data-table-rows] thead', 'MY IMAGINARY COLUMN');
        });
    }
}
