<?php

namespace Gogol\Admin\Tests\Commands;

use Gogol\Admin\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminInstallCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp() : void
    {
        parent::setUp();

        $this->installAdmin()
             ->expectsOutput('+ Vendor directories has been successfully published')
             ->expectsOutput('+ Demo user created')
             ->expectsOutput('Installation completed!')
             ->assertExitCode(0);
    }

    protected function tearDown() : void
    {
        $this->uninstallAdmin();

        parent::tearDown();
    }

    /** @test */
    public function check_if_is_published_resources()
    {
        foreach ($this->getPublishableResources() as $path)
            $this->assertFileExists($path);
    }

    /** @test */
    public function check_if_is_demo_user_has_been_created()
    {
        $this->assertDatabaseHas('users', [
            'email' => $this->credentials['email']
        ]);
    }
}
