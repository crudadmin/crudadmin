<?php

namespace Admin\Tests\Feature\Model;

use Admin\Facades\Admin;
use Admin\Eloquent\AdminModel;
use Admin\Tests\App\OtherModels\Blog;
use Admin\Tests\TestCase;

class MethodTest extends TestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        $this->installAdmin();
    }

    /** @test */
    public function get_model_by_table()
    {
        Admin::registerAdminModels($this->getAppPath('OtherModels'), 'Admin\Tests\App\OtherModels');

        $this->assertNull(Admin::getModelByTable('blog'));
        $this->assertInstanceOf(AdminModel::class, Admin::getModelByTable('blogs'));
    }

    /** @test */
    public function get_model_by_classname()
    {
        Admin::registerAdminModels($this->getAppPath('OtherModels'), 'Admin\Tests\App\OtherModels');

        $this->assertNull(Admin::getModel('blogs'));
        $this->assertInstanceOf(AdminModel::class, Admin::getModel('blog'));
        $this->assertInstanceOf(AdminModel::class, Admin::getModel('Blog'));
    }

    /** @test */
    public function check_if_is_admin_model()
    {
        $this->assertTrue(Admin::isAdminModel(new Blog));
    }


    /** @test */
    public function check_if_has_admin_model()
    {
        Admin::registerAdminModels($this->getAppPath('OtherModels'), 'Admin\Tests\App\OtherModels');

        $this->assertTrue(Admin::hasAdminModel('Blog'));
        $this->assertTrue(Admin::hasAdminModel('BlogsImage'));
        $this->assertFalse(Admin::hasAdminModel('BlogsImages'));
    }
}
