<?php

namespace Gogol\Admin\Hashing;

use Illuminate\Hashing\HashManager as DefaultHashManager;
use Gogol\Admin\Hashing\BcryptHasher;

class HashManager extends DefaultHashManager
{
    /**
     * Create an instance of the Bcrypt hash Driver.
     *
     * @return BcryptHasher
     */
    public function createBcryptDriver()
    {
        return new BcryptHasher;
    }
}