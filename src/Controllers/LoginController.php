<?php

namespace Admin\Controllers;

use AdminHelpers\Auth\Controllers\LoginController as BaseLoginController;
use Admin;

class LoginController extends BaseLoginController
{
    public function getAuthModel()
    {
        return Admin::getAuthModel();
    }
}