<?php

namespace Gogol\Admin\Models;

use Illuminate\Auth\Authenticatable as BaseAuthenticatable;
use Gogol\Admin\Models\Model;
use Gogol\Admin\Traits\Auth\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class Authenticatable extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use BaseAuthenticatable, Authorizable, CanResetPassword;

    public function setPasswordAttribute($value)
    {
        if ( $value && strlen($value) == 60)
            $this->attributes['password'] = $value;
        elseif ( $value )
            $this->attributes['password'] = bcrypt($value);
    }
}
