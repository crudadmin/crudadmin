<?php

namespace Gogol\Admin\Tests;

use Gogol\Admin\Tests\Traits\AdminTrait;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use AdminTrait;
}
