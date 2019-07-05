<?php

namespace Admin\Tests\Feature\Model;

use Admin\Facades\Admin;
use Admin\Tests\TestCase;

class RegisterTest extends TestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        $this->installAdmin();
    }

    /** @test */
    public function models_loaded_dynamically_from_package()
    {
        //Register dynamically admin model
        Admin::registerAdminModels($this->getAppPath('OtherModels'), 'Admin\Tests\App\OtherModels');

        $this->assertEquals(Admin::getAdminModelNamespaces(), [
            '2016-06-05 00:00:00' => 'Admin\Models\Language',
            '2016-07-09 17:27:57' => 'Admin\Tests\App\User',
            '2017-07-15 00:00:00' => 'Admin\Models\ModelsHistory',
            '2019-05-03 13:10:04' => 'Admin\Tests\App\OtherModels\Blog',
            '2019-05-03 14:11:02' => 'Admin\Tests\App\OtherModels\BlogsImage'
        ]);
    }
}
