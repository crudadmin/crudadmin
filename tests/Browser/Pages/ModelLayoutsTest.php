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
                    ->assertSee('Vue component TopLayout Model 3')
                    ->assertSee('Vue component BottomLayout Model 3')
                    ->assertSee('Vue component FormTop Model 3')
                    ->assertSee('Vue component FormBottom Model 3')
                    ->assertSee('Vue component FormHeader Model 3')
                    ->assertSee('Vue component FormFooter Model 3')
                    ->assertSee('Vue component TableHeader Model 3')
                    ->assertSee('Vue component TableFooter Model 3')

                    //Test also component change after row open
                    ->openRow(1)
                    ->assertSee('Vue component TopLayout Model 3 id 1')
                    ->assertSee('Vue component BottomLayout Model 3 id 1')
                    ->assertSee('Vue component FormTop Model 3 id 1')
                    ->assertSee('Vue component FormBottom Model 3 id 1')
                    ->assertSee('Vue component FormHeader Model 3 id 1')
                    ->assertSee('Vue component FormFooter Model 3 id 1')
                    ->assertSee('Vue component TableHeader Model 3 id 1')
                    ->assertSee('Vue component TableFooter Model 3 id 1');
        });
    }
}