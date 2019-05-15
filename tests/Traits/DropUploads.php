<?php

namespace Gogol\Admin\Tests\Traits;

trait DropUploads
{
    /*
     * Drop all tables in database
     */
    public function dropUploads(){
        $this->deleteFileOrDirectory(public_path('uploads'));
    }
}