<?php

namespace Gogol\Admin\Tests\Browser\Pages;

use Gogol\Admin\Tests\App\Models\Tree\Model3;
use Gogol\Admin\Tests\Browser\BrowserTestCase;
use Gogol\Admin\Tests\Browser\DuskBrowser;
use Gogol\Admin\Tests\Traits\DropDatabase;

class ModelLayoutsTest extends BrowserTestCase
{
    use DropDatabase;

    /** @test */
    public function test_layouts()
    {
        Model3::create([
            'field1' => 'test item',
        ]);

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Model3::class)
                    ->assertSee('Vue component TopLayout Model layouts')
                    ->assertSee('Vue component BottomLayout Model layouts')
                    ->assertSee('Vue component FormTop Model layouts')
                    ->assertSee('Vue component FormBottom Model layouts')
                    ->assertSee('Vue component FormHeader Model layouts')
                    ->assertSee('Vue component FormFooter Model layouts')
                    ->assertSee('Vue component TableHeader Model layouts')
                    ->assertSee('Vue component TableFooter Model layouts')

                    //Test also component change after row open
                    ->openRow(1)
                    ->assertSee('Vue component TopLayout Model layouts id 1')
                    ->assertSee('Vue component BottomLayout Model layouts id 1')
                    ->assertSee('Vue component FormTop Model layouts id 1')
                    ->assertSee('Vue component FormBottom Model layouts id 1')
                    ->assertSee('Vue component FormHeader Model layouts id 1')
                    ->assertSee('Vue component FormFooter Model layouts id 1')
                    ->assertSee('Vue component TableHeader Model layouts id 1')
                    ->assertSee('Vue component TableFooter Model layouts id 1');
        });
    }
}