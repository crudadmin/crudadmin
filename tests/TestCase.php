<?php

namespace Gogol\Admin\Tests;

use Gogol\Admin\Tests\AdminTrait;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use AdminTrait;

    /*
     * Delete file, or whole directory
     */
    protected function deleteFileOrDirectory($path)
    {
        if ( is_dir($path) )
            File::deleteDirectory($path);
        else
            @unlink($path);
    }

    /*
     * All published admin resources
     */
    protected function getPublishableResources()
    {
        return [
            config_path('admin.php'),
            resource_path('lang/cs'),
            resource_path('lang/sk'),
            public_path('vendor/crudadmin/dist/version'),
        ];
    }

    /*
     * All admin resources
     */
    protected function getAdminResources()
    {
        $resources = [];

        //Add publishable resources
        foreach ($this->getPublishableResources() as $item)
            $resources[] = $item;

        return $resources;
    }
}
