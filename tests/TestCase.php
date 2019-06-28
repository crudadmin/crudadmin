<?php

namespace Admin\Tests;

use Admin\Tests\Concerns\AdminIntegration;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Admin\Tests\OrchestraSetup;

class TestCase extends BaseTestCase
{
    use OrchestraSetup,
        AdminIntegration;
}
