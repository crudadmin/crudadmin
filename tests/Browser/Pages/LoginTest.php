<?php

namespace Gogol\Admin\Tests\Browser\Pages;

use Gogol\Admin\Tests\Browser\BrowserTestCase;
use Gogol\Admin\Tests\Browser\DuskBrowser;

class LoginTest extends BrowserTestCase
{
    /** @test */
    public function can_authenticate_user()
    {
        $this->browse(function (DuskBrowser $browser) {
            $browser->visit(admin_action('Auth\LoginController@showLoginForm'))
                    ->assertSee('My Admin')
                    ->type('email', $this->credentials['email'])
                    ->type('password', $this->credentials['password'])
                    ->press(trans('admin::admin.login'))
                    ->assertUrlIs(admin_action('DashboardController@index'));
        });
    }
}