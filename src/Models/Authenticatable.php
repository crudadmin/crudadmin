<?php

namespace Gogol\Admin\Models;

use Illuminate\Auth\Authenticatable as BaseAuthenticatable;
use Gogol\Admin\Models\Model;
use Gogol\Admin\Traits\Auth\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Schema;
use Admin;

class Authenticatable extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use BaseAuthenticatable, Authorizable, CanResetPassword;

    /*
     * Skipping dropping columns
     */
    protected $skipDropping = ['remember_token'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /*
     * Enable sorting rows
     */
    protected $sortable = false;

    /*
     * Enable publishing rows
     */
    protected $publishable = false;

    /*
     * Guard for authentification model
     */
    protected $guard = 'web';

    /*
     * Get all allowed models from all groups which user owns
     */
    public function permissions()
    {
        $key = 'users.'.$this->getKey().'.permissions';

        //Check for buffer
        if ( Admin::has($key) )
            return Admin::get($key);

        $models = [];

        foreach ((array)$this->adminsGroups as $group)
        {
            $models = array_merge($models, (array)$group->models);
        }

        return Admin::save($key, $models);
    }

    /*
     * Check if has user allowed model by namespace
     */
    public function hasAccess($model)
    {
        if ( config('admin.admin_groups') !== true )
            return true;

        //If is super admin
        if ( $this->hasAdminAccess() )
            return true;

        if ( is_object($model) )
            $model = get_class($model);
        else
            $model = trim($model, '/');

        return in_array($model, $this->permissions());
    }

    /*
     * Check if is user enabled
     */
    public function hasAdminAccess()
    {
        return $this->permissions == 1;
    }

    /*
     * Checks if is enabled user
     */
    public function isEnabled()
    {
        return $this->enabled == 1;
    }

    /*
     * Automatically change value in password attribute
     */
    public function setPasswordAttribute($value)
    {
        if ( $value && strlen($value) == 60)
            $this->attributes['password'] = $value;
        elseif ( $value )
            $this->attributes['password'] = bcrypt($value);
    }

    /*
     * Disabling for change own permissions value
     */
    public function setEnabledAttribute($value)
    {
        if ( Admin::isAdmin() && $this->exists == true && $this->getKey() === auth()->guard( $this->guard )->user()->getKey() && $this->enabled != $value )
        {
            return Admin::push('errors.request', 'Nie je možné deaktivovať vlastný účet.');
        }

        $this->attributes['enabled'] = $value;
    }

    /*
     * Disabling for change own permissions value
     */
    public function setPermissionsAttribute($value)
    {
        if ( Admin::isAdmin() && $this->exists == true && $this->getKey() === auth()->guard( $this->guard )->user()->getKey() && $this->permissions != $value )
        {
            return Admin::push('errors.request', 'Nie je možné upravovať vlastne administrátorske práva.');
        }

        $this->attributes['permissions'] = $value;
    }

    public function withAvatarPath()
    {
        if ( $this->avatar )
            $this->avatar = $this->avatar->thumbs->path;

        return $this;
    }

    /*
     * Add columns
     */
    public function onMigrate($table, $schema)
    {
        //Add remember token into user table
        if ( ! $schema->hasColumn( $this->getTable(), 'remember_token') )
        {
            $column = $table->rememberToken();

            //add remember token after this columns
            if ( $schema->hasColumn( $this->getTable(), 'deleted_at') )
                $column->before('deleted_at');
            else if ( $schema->hasColumn( $this->getTable(), 'avatar') )
                $column->after('avatar');
        }
    }
}
