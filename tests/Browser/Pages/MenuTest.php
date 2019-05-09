<?php

namespace Gogol\Admin\Tests\Browser\Pages;

use Gogol\Admin\Tests\Browser\BrowserTestCase;
use Illuminate\Foundation\Auth\User;
use Laravel\Dusk\Browser;

class MenuTest extends BrowserTestCase
{
    /** @test */
    public function can_authenticate_user()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::first())
                    ->visit(admin_action('DashboardController@index'))
                    ->assertSeeLink('Nastavenia')
                    ->clickLink('Nastavenia')
                    ->assertSeeLink('Administrátori');
        });
    }
}