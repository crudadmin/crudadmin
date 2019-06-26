<?php

namespace Gogol\Admin\Tests\Concerns;

trait DropUploads
{
    /*
     * Drop all tables in database
     */
    public function dropUploads(){
        $this->deleteFileOrDirectory(public_path('uploads'));
    }
}