<?php

namespace Gogol\Admin\Tests\Browser\Pages;

use Gogol\Admin\Tests\App\Models\Tree\Model1;
use Gogol\Admin\Tests\Browser\BrowserTestCase;
use Gogol\Admin\Tests\Browser\DuskBrowser;
use Gogol\Admin\Tests\Traits\DropDatabase;

class ModelSettingsTest extends BrowserTestCase
{
    use DropDatabase;

    /** @test */
    public function test_model_titles_and_buttons_texts()
    {
        Model1::create([ 'field1' => 'test item' ]);
        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Model1::class)
                    ->assertSeeIn('[data-header]', 'Hlavička nového záznamu')
                    ->assertSeeIn('[data-footer]', 'Odoslať nový záznam')
                    ->openRow(1)
                    ->assertSeeIn('[data-header]', 'Vytvoriť nový záznam')
                    ->assertSeeIn('[data-header]', 'Upravujete záznam č. 1 test item')
                    ->assertSeeIn('[data-footer]', 'Upraviť starý záznam');
        });
    }
}