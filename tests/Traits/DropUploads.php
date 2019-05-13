<?php

namespace Gogol\Admin\Tests\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

trait DropUploads
{
    /*
     * Drop all tables in database
     */
    public function dropUploads(){
        $this->deleteFileOrDirectory(public_path('uploads'));
    }
}