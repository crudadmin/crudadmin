<?php

namespace Gogol\Admin\Tests;

use Gogol\Admin\Tests\Concerns\AdminIntegration;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Gogol\Admin\Tests\OrchestraSetup;

class TestCase extends BaseTestCase
{
    use OrchestraSetup,
        AdminIntegration;
}
