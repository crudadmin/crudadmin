<?php

namespace Gogol\Admin\Tests\Auth;

use Gogol\Admin\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp() : void
    {
        parent::setUp();

        $this->installAdmin();
    }

    /** @test */
    public function check_if_is_demo_user_can_log_in()
    {
        $response = $this->json('POST', action('\Gogol\Admin\Controllers\Auth\LoginController@login'), [
            'email' => $this->credentials['email'],
            'password' => $this->credentials['password'],
        ]);

        $response->assertRedirect(action('\Gogol\Admin\Controllers\DashboardController@index'));
    }
}
