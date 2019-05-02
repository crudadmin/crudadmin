<?php

namespace Gogol\Admin\Tests;

use Illuminate\Support\Facades\File;

trait TestCaseTrait
{
    public function deleteFileOrDirectory($path)
    {
        if ( is_dir($path) )
            File::deleteDirectory($path);
        else
            @unlink(config_path('admin.php'));
    }
}

?>