<?php

namespace Gogol\Admin\Tests\Browser\Pages;

use Gogol\Admin\Tests\Browser\BrowserTestCase;
use Illuminate\Foundation\Auth\User;
use Laravel\Dusk\Browser;

class MenuTest extends BrowserTestCase
{
    /** @test */
    public function is_menu_available()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::first())
                    ->visit(admin_action('DashboardController@index'))
                    ->assertSeeLink('Nastavenia')
                    ->clickLink('Nastavenia')
                    ->assertSeeLink('Administrátori');
        });
    }

    /** @test */
    public function menu_item_is_active()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::first())
                    ->visit(admin_action('DashboardController@index'))
                    ->clickLink('Nastavenia')
                    ->pause(1000)
                    ->clickLink('Administrátori', 'li a')
                    ->assertHasClass('li[data-slug="users"]', 'active');
        });
    }
}