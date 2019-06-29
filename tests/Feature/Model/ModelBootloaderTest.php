<?php

namespace Admin\Tests\Feature\Model;

use Admin\Facades\Admin;
use Admin\Eloquent\AdminModel;
use Admin\Tests\App\OtherModels\Blog;
use Admin\Tests\TestCase;

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
            '2016-06-05 00:00:00' => 'Admin\Models\Language',
            '2016-07-09 17:27:57' => 'Admin\Tests\App\User',
            '2017-07-15 00:00:00' => 'Admin\Models\ModelsHistory',
        ]);
    }

    /** @test */
    public function models_from_config_directory_are_available()
    {
        $this->registerAllAdminModels();

        $this->assertEquals(Admin::boot(true), [
            '2016-06-05 00:00:00' => 'Admin\Models\Language',
            '2016-07-09 17:27:57' => 'Admin\Tests\App\User',
            '2017-07-15 00:00:00' => 'Admin\Models\ModelsHistory',
            '2019-05-03 12:02:04' => 'Admin\Tests\App\Models\Fields\FieldsType',
            '2019-05-03 12:12:04' => 'Admin\Tests\App\Models\Fields\FieldsTypesMultiple',
            '2019-05-03 11:11:02' => 'Admin\Tests\App\Models\Fields\FieldsGroup',
            '2019-05-03 14:12:04' => 'Admin\Tests\App\Models\Fields\FieldsRelation',
            '2019-05-03 15:12:04' => 'Admin\Tests\App\Models\Fields\FieldsMutator',
            '2019-05-03 15:13:07' => 'Admin\Tests\App\Models\Fields\SelectType',
            '2019-05-04 12:10:04' => 'Admin\Tests\App\Models\Articles\Article',
            '2019-05-04 12:10:15' => 'Admin\Tests\App\Models\Articles\ArticlesComment',
            '2019-05-04 12:10:24' => 'Admin\Tests\App\Models\Articles\Tag',
            '2019-05-15 12:10:02' => 'Admin\Tests\App\Models\Tree\Model1',
            '2019-05-15 12:11:02' => 'Admin\Tests\App\Models\Tree\Model2',
            '2019-05-15 12:12:02' => 'Admin\Tests\App\Models\Tree\Model3',
            '2019-07-13 15:05:04' => 'Admin\Tests\App\Models\Locales\ModelLocalization',
            '2019-07-13 15:06:05' => 'Admin\Tests\App\Models\Locales\ModelLocale',
            '2019-07-14 12:10:05' => 'Admin\Tests\App\Models\History\History',
        ]);
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
