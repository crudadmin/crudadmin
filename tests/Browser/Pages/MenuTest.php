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
                    ->waitForLink('Administrátori')->pause(500)
                    ->clickLink('Administrátori', 'li a')
                    ->assertHasClass('li[data-slug="users"]', 'active');
        });
    }

    /** @test */
    public function is_recursive_menu_available_and_active_works()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::first())
                    ->visit(admin_action('DashboardController@index'))
                    ->assertSeeLink('My tree level 1')
                    ->clickLink('My tree level 1')

                    //click on sub sub level
                    ->waitForLink('My subtree level')
                    ->clickLink('My subtree level')

                    //Open model 2 and check if submenu and model is active
                    ->waitForLink('Model 2')->pause(500)
                    ->clickLink('Model 2', 'li a')
                    ->assertHasClass('li[data-slug="#$_level1.level2"]', 'active')
                    ->assertHasClass('li[data-slug="model2s"]', 'active');

            //Reload page and check if link are active
            $browser->script('window.location.reload()');
            $browser->assertHasClass('li[data-slug="#$_level1.level2"]', 'active')
                    ->assertHasClass('li[data-slug="model2s"]', 'active');
        });
    }
}