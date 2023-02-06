<?php

namespace Admin\Tests\Feature\Model;

use Admin\Facades\Admin;
use Admin\Tests\TestCase;

class DefaultTest extends TestCase
{
    /** @test */
    public function only_default_models_are_available()
    {
        $this->assertEquals(Admin::boot(), [
            '2016-06-05 00:00:00' => 'Admin\Models\Language',
            '2016-07-09 17:27:57' => 'Admin\Models\Admin',
            '2017-07-15 00:00:00' => 'Admin\Models\ModelsHistory',
        ]);
    }
}
