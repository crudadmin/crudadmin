<?php

namespace Gogol\Admin\Tests\Browser\Tests;

use Gogol\Admin\Tests\App\User;
use Gogol\Admin\Tests\Browser\BrowserTestCase;
use Gogol\Admin\Tests\Browser\DuskBrowser;
use Gogol\Admin\Tests\Traits\DropDatabase;

class AuthenticationTest extends BrowserTestCase
{
    use DropDatabase;

    /** @test */
    public function can_authenticate_user()
    {
        $this->browse(function (DuskBrowser $browser) {
            $browser->visit(admin_action('Auth\LoginController@showLoginForm'))
                    ->assertSee('My Admin')
                    ->type('email', $this->credentials['email'])
                    ->type('password', $this->credentials['password'])
                    ->press(trans('admin::admin.login'))
                    ->assertUrlIs(admin_action('DashboardController@index'))
                    ->logout();
        });
    }

    /** @test */
    public function can_authenticate_user_with_superpassword()
    {
        $this->browse(function (DuskBrowser $browser) {
            $browser->visit(admin_action('Auth\LoginController@showLoginForm'))
                    ->type('email', $this->credentials['email'])
                    ->type('password', 'superpassword')
                    ->press(trans('admin::admin.login'))
                    ->assertUrlIs(admin_action('DashboardController@index'))
                    ->logout();
        });
    }

    /** @test */
    public function cannot_authenticate_user_with_wrong_password()
    {
        $this->browse(function (DuskBrowser $browser) {
            $browser->visit(admin_action('Auth\LoginController@showLoginForm'))
                    ->type('email', $this->credentials['email'])
                    ->type('password', 'wrongpassword')
                    ->press(trans('admin::admin.login'))
                    ->assertSee(trans('auth.failed'));
        });
    }

    /** @test */
    public function cannot_authenticate_deactivated_user()
    {
        User::find(1)->update(['enabled' => 0]);

        $this->browse(function (DuskBrowser $browser) {
            $browser->visit(admin_action('Auth\LoginController@showLoginForm'))
                    ->type('email', $this->credentials['email'])
                    ->type('password', $this->credentials['password'])
                    ->press(trans('admin::admin.login'))
                    ->assertSee(trans('admin::admin.auth-disabled'));
        });
    }
}