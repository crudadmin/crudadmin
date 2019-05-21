<?php

namespace Gogol\Admin\Tests\Browser\Pages;

use Gogol\Admin\Tests\Browser\BrowserTestCase;
use Illuminate\Foundation\Auth\User;
use Laravel\Dusk\Browser;

class MenuTest extends BrowserTestCase
{
    /** @test */
    public function is_menu_available_and_active_works()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::first())
                    ->visit(admin_action('DashboardController@index'))
                    ->assertSeeLink('Nastavenia')
                    ->clickLink('Nastavenia')
                    ->waitForLink('Administrátori')
                    ->pause(500)
                    ->clickLink('Administrátori', 'li a')
                    ->assertHasClass('li[data-slug="users"]', 'active');
        });
    }
}