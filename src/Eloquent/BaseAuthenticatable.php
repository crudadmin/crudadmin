<?php

namespace Admin\Eloquent;

use Admin;
use Admin\Eloquent\Concerns\Auth\CanResetPassword;
use Admin\Eloquent\Concerns\Auth\HasPasswordSetter;
use Admin\Eloquent\Modules\VerificationModule;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;

class BaseAuthenticatable extends AdminModel implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, Notifiable, MustVerifyEmail, HasPasswordSetter;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [ 'password', 'remember_token' ];

    /*
     * Enable sorting rows
     */
    protected $sortable = false;

    /*
     * Disable publishing rows
     */
    protected $publishable = false;

    /*
     * Guard for authentification model
     */
    protected $guard = null;

    /**
     * Guard of model
     *
     * @return  Guard
     */
    public function getGuard()
    {
        if ( $this->guard ){
            return auth()->guard($this->guard);
        }

        return Admin::getAdminGuard();
    }
}
