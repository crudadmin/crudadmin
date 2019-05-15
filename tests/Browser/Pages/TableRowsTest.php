<?php

namespace Gogol\Admin\Tests\Browser\Pages;

use Artisan;
use Carbon\Carbon;
use Gogol\Admin\Tests\App\Models\Articles\Article;
use Gogol\Admin\Tests\Browser\BrowserTestCase;
use Gogol\Admin\Tests\Browser\DuskBrowser;
use Gogol\Admin\Tests\Traits\DropDatabase;
use Illuminate\Foundation\Auth\User;

class TableRowsTest extends BrowserTestCase
{
    use DropDatabase;

    /**
     * Setup the test environment.
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->withFactories(__DIR__.'/../../Factories');
    }

    /** @test */
    public function test_pagination()
    {
        //Create 100 articles
        factory(Article::class, 100)->create();

        $this->browse(function (DuskBrowser $browser) {
            $browser->loginAs(User::first())
                    ->visit(admin_action('DashboardController@index'))
                    ->assertSeeLink('Články')
                    ->clickLink('Články')

                    ->pause(100000);
        });
    }
}