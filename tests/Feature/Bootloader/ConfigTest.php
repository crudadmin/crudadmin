<?php

namespace Admin\Tests\Feature\Model;

use Admin\Facades\Admin;
use Admin\Tests\TestCase;

class ConfigTest extends TestCase
{
    /*
     * Load all admin models into each test
     */
    protected $loadAllAdminModels = true;

    /** @test */
    public function test_models_loaded_from_config_with_default_user_model_rewrition()
    {
        $this->assertEquals(Admin::boot(), [
            '2016-06-05 00:00:00' => 'Admin\Models\Language',
            '2016-07-09 17:27:57' => 'Admin\Tests\App\User',
            '2017-07-15 00:00:00' => 'Admin\Models\ModelsHistory',
            '2019-05-03 12:02:04' => 'Admin\Tests\App\Models\Fields\FieldsType',
            '2019-05-03 12:12:04' => 'Admin\Tests\App\Models\Fields\FieldsTypesMultiple',
            '2019-05-03 11:11:02' => 'Admin\Tests\App\Models\Fields\FieldsGroup',
            '2019-05-03 14:12:04' => 'Admin\Tests\App\Models\Fields\FieldsRelation',
            '2019-05-03 15:12:04' => 'Admin\Tests\App\Models\Fields\FieldsMutator',
            '2019-05-03 15:13:07' => 'Admin\Tests\App\Models\Fields\SelectType',
            '2019-05-04 10:10:04' => 'Admin\Tests\App\OtherModels\Blog',
            '2019-05-04 10:11:02' => 'Admin\Tests\App\OtherModels\BlogsImage',
            '2019-05-04 12:10:04' => 'Admin\Tests\App\Models\Articles\Article',
            '2019-05-04 12:10:15' => 'Admin\Tests\App\Models\Articles\ArticlesComment',
            '2019-05-04 12:10:24' => 'Admin\Tests\App\Models\Articles\Tag',
            '2019-05-15 12:10:02' => 'Admin\Tests\App\Models\Tree\Model1',
            '2019-05-15 12:11:02' => 'Admin\Tests\App\Models\Tree\Model2',
            '2019-05-15 12:12:02' => 'Admin\Tests\App\Models\Tree\Model3',
            '2019-07-13 15:05:04' => 'Admin\Tests\App\Models\Locales\ModelLocalization',
            '2019-07-13 15:06:05' => 'Admin\Tests\App\Models\Locales\ModelLocale',
            '2019-07-14 12:10:05' => 'Admin\Tests\App\Models\History\History',
            '2019-09-16 11:10:04' => 'Admin\Tests\App\Models\Single\SingleModel',
            '2019-09-16 11:15:04' => 'Admin\Tests\App\Models\Single\SingleModelRelation',
        ]);
    }
}
