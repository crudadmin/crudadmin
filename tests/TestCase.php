<?php

namespace Gogol\Admin\Tests;

use Gogol\Admin\Tests\AdminTrait;
use Gogol\Admin\Tests\TestCaseTrait;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use AdminTrait;
    use TestCaseTrait;
}
