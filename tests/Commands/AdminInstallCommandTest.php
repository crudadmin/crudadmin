<?php

namespace Gogol\Admin\Tests\Commands;

use Gogol\Admin\Models\User;
use Gogol\Admin\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

class AdminInstallCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp() : void
    {
        parent::setUp();

        $this->artisan('admin:install')
             ->expectsOutput('+ Vendor directories has been successfully published')
             ->expectsOutput('+ Demo user created')
             ->expectsOutput('Installation completed!')
             ->assertExitCode(0);
    }

    protected function tearDown() : void
    {
        //Remove all published resources
        foreach ($this->getPublishableResources() as $path)
        {
            if ( is_dir($path) )
                File::deleteDirectory($path);
            else
                @unlink(config_path('admin.php'));
        }

        parent::tearDown();
    }

    private function getPublishableResources()
    {
        return [
            config_path('admin.php'),
            resource_path('lang/cs'),
            resource_path('lang/sk'),
            public_path('vendor/crudadmin'),
        ];
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
            'email' => 'admin@admin.com'
        ]);
    }


}
