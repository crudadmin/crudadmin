<?php

namespace Admin\Tests;

use Admin\Tests\Concerns\AdminIntegration;
use Admin\Tests\OrchestraSetup;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use OrchestraSetup,
        AdminIntegration;
}
