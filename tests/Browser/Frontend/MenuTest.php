<?php

namespace Gogol\Admin\Tests\Browser\Frontend;

use Gogol\Admin\Tests\Browser\BrowserTestCase;
use Illuminate\Foundation\Auth\User;
use Laravel\Dusk\Browser;

class MenuTest extends BrowserTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->installAdmin();
    }

    /** @test */
    public function can_authenticate_user()
    {
        $user = new User;
        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit(action('\Gogol\Admin\Controllers\Auth\LoginController@showLoginForm'))
                    ->assertSee('My Admin');
        });
    }
}