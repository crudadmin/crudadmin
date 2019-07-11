<?php

namespace Admin\Tests\Feature\Auth;

use Admin\Tests\TestCase;
use Admin\Tests\Concerns\DropDatabase;
use Illuminate\Support\Facades\File;

class AdminAuthTest extends TestCase
{
    use DropDatabase;

    protected function setUp() : void
    {
        parent::setUp();

        $this->installAdmin();
    }

    /** @test */
    public function check_if_is_demo_user_can_log_in()
    {
        $response = $this->json('POST', admin_action('Auth\LoginController@login'), [
            'email' => $this->credentials['email'],
            'password' => $this->credentials['password'],
        ]);

        $response->assertRedirect(admin_action('DashboardController@index'));
    }
}
