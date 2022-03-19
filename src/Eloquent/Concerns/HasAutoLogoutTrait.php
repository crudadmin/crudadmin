<?php

namespace Admin\Eloquent\Concerns;

use Admin\Admin\Buttons\LogoutUser;

trait HasAutoLogoutTrait
{
    public function getLogoutSessionKey()
    {
        return $this->getTable().'.logout.timestamp';
    }

    public function hasAutoLogoutSupport()
    {
        return count(array_filter($this->getProperty('buttons') ?: [], function($buttonClass){
            return $buttonClass == LogoutUser::class;
        })) > 0;
    }

    public function getLogoutTimestamp()
    {
        return session()->get($this->getLogoutSessionKey());
    }

    public function setLogoutTimestamp($date = null)
    {
        session()->put($this->getLogoutSessionKey(), $date->getTimestamp());
        session()->save();
    }
}
