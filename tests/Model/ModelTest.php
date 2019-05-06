<?php

namespace Gogol\Admin\Tests\Model;

use Gogol\Admin\Facades\Admin;
use Gogol\Admin\Tests\TestCase;

class ModelTest extends TestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        $this->installAdmin();
    }

    /** @test */
    public function only_user_model_is_available()
    {
        $this->assertArraySubset(Admin::boot(), [
            '2016-07-09 17:27:57' => 'Gogol\Admin\Tests\App\User'
        ]);
    }

    /** @test */
    public function models_from_config_directory_are_available()
    {
        config()->set('admin.models', [
            'Gogol\Admin\Tests\App\Models' => __DIR__.'/../Stubs/app/Models/*'
        ]);

        $this->assertArraySubset(Admin::boot(true), [
            '2016-07-09 17:27:57' => 'Gogol\Admin\Tests\App\User',
            '2019-05-03 11:10:04' => 'Gogol\Admin\Tests\App\Models\FieldsType',
            '2019-05-03 12:10:04' => 'Gogol\Admin\Tests\App\Models\Articles\Article',
        ]);
    }
}
