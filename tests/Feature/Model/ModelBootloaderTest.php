<?php

namespace Gogol\Admin\Tests\Feature\Model;

use Gogol\Admin\Facades\Admin;
use Gogol\Admin\Models\Model as AdminModel;
use Gogol\Admin\Tests\App\OtherModels\Blog;
use Gogol\Admin\Tests\TestCase;

class ModelBootloaderTest extends TestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        $this->installAdmin();
    }

    /** @test */
    public function only_default_models_are_available()
    {
        $this->assertEquals(Admin::boot(), [
            '2016-06-05 00:00:00' => 'Gogol\Admin\Models\Language',
            '2016-07-09 17:27:57' => 'Gogol\Admin\Tests\App\User'
        ]);
    }

    /** @test */
    public function models_from_config_directory_are_available()
    {
        $this->registerAllAdminModels();

        $this->assertEquals(Admin::boot(true), [
            '2016-06-05 00:00:00' => 'Gogol\Admin\Models\Language',
            '2016-07-09 17:27:57' => 'Gogol\Admin\Tests\App\User',
            '2019-05-03 12:02:04' => 'Gogol\Admin\Tests\App\Models\FieldsType',
            '2019-05-03 12:12:04' => 'Gogol\Admin\Tests\App\Models\FieldsTypesMultiple',
            '2019-05-03 11:11:02' => 'Gogol\Admin\Tests\App\Models\FieldsGroup',
            '2019-05-03 14:12:04' => 'Gogol\Admin\Tests\App\Models\FieldsRelation',
            '2019-05-03 15:12:04' => 'Gogol\Admin\Tests\App\Models\FieldsMutator',
            '2019-05-04 12:10:04' => 'Gogol\Admin\Tests\App\Models\Articles\Article',
            '2019-05-04 12:10:15' => 'Gogol\Admin\Tests\App\Models\Articles\ArticlesComment',
            '2019-05-04 12:10:24' => 'Gogol\Admin\Tests\App\Models\Articles\Tag',
            '2019-05-15 12:10:02' => 'Gogol\Admin\Tests\App\Models\Tree\Model1',
            '2019-05-15 12:11:02' => 'Gogol\Admin\Tests\App\Models\Tree\Model2',
            '2019-05-15 12:12:02' => 'Gogol\Admin\Tests\App\Models\Tree\Model3',
        ]);
    }

    /** @test */
    public function models_loaded_dynamically_from_package()
    {
        //Register dynamically admin model
        Admin::registerAdminModels($this->getAppPath('OtherModels'), 'Gogol\Admin\Tests\App\OtherModels');

        $this->assertEquals(Admin::getAdminModelNamespaces(), [
            '2016-06-05 00:00:00' => 'Gogol\Admin\Models\Language',
            '2016-07-09 17:27:57' => 'Gogol\Admin\Tests\App\User',
            '2019-05-03 13:10:04' => 'Gogol\Admin\Tests\App\OtherModels\Blog',
            '2019-05-03 14:11:02' => 'Gogol\Admin\Tests\App\OtherModels\BlogsImage'
        ]);
    }

    /** @test */
    public function get_model_by_table()
    {
        Admin::registerAdminModels($this->getAppPath('OtherModels'), 'Gogol\Admin\Tests\App\OtherModels');

        $this->assertNull(Admin::getModelByTable('blog'));
        $this->assertInstanceOf(AdminModel::class, Admin::getModelByTable('blogs'));
    }

    /** @test */
    public function get_model_by_classname()
    {
        Admin::registerAdminModels($this->getAppPath('OtherModels'), 'Gogol\Admin\Tests\App\OtherModels');

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
        Admin::registerAdminModels($this->getAppPath('OtherModels'), 'Gogol\Admin\Tests\App\OtherModels');

        $this->assertTrue(Admin::hasAdminModel('Blog'));
        $this->assertTrue(Admin::hasAdminModel('BlogsImage'));
        $this->assertFalse(Admin::hasAdminModel('BlogsImages'));
    }
}
