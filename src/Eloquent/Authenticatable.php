<?php

namespace Admin\Eloquent;

use Admin;
use Illuminate\Notifications\Notifiable;
use Admin\Eloquent\Concerns\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Auth\Authenticatable as BaseAuthenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class Authenticatable extends AdminModel implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use BaseAuthenticatable, Authorizable, CanResetPassword, Notifiable;

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
     * Disable publishing rows
     */
    protected $publishable = false;

    /*
     * Guard for authentification model
     */
    protected $guard = 'web';

    /*
     * Add Admin rules permissions
     */
    protected $withUserRoles = false;

    public function __construct(array $attributes = [])
    {
        if (Admin::isFrontend()) {
            $this->publishable = false;
        }

        parent::__construct($attributes);
    }

    /*
     * Get all allowed models from all groups which user owns
     */
    public function getUserPermissions()
    {
        if (Admin::isRolesEnabled() === false) {
            return [];
        }

        $key = 'users.'.$this->getKey().'.permissions';

        //Check for buffer
        if (Admin::has($key)) {
            return Admin::get($key);
        }

        $models = [];

        if ($admin_groups = $this->roles) {
            foreach ($admin_groups as $group) {
                $permissions = (array) json_decode($group->permissions ?: '{}', true);

                //Remove all disabled permissions
                foreach ($permissions as $modelKey => $model) {
                    foreach ($model as $permissionKey => $state) {
                        if ( $state === false ) {
                            unset($permissions[$modelKey][$permissionKey]);
                        }
                    }
                }

                $models = array_merge($models, $permissions);
            }
        }

        return Admin::set($key, $models);
    }

    /*
     * Check if has user allowed model by namespace
     */
    public function hasAccess($model, $permissionKey = true)
    {
        //If roles are not enabled, allow everything
        if (Admin::isRolesEnabled() === false) {
            return true;
        }

        //If is super admin
        if ($this->hasAdminAccess()) {
            return true;
        }

        if (is_object($model)) {
            $model = get_class($model);
        } else {
            $model = trim($model, '/');
        }

        $permissions = $this->getUserPermissions();

        //Check if any permission is present
        if ( $permissionKey === true ) {
            //Check if has at least one true permission
            if ( array_key_exists($model, $permissions) && count(array_keys($permissions[$model])) > 0 )
                return true;

            return false;
        }

        return array_key_exists($model, $permissions) && @$permissions[$model][$permissionKey] === true;
    }

    /*
     * Check if is user super administrator with all permissions
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
        if ($value && strlen($value) == 60) {
            $this->attributes['password'] = $value;
        } elseif ($value === null) {
            $this->attributes['password'] = null;
        } elseif ($value || is_numeric($value)) {
            $this->attributes['password'] = bcrypt($value);
        }
    }

    /*
     * Disabling for change own permissions value
     */
    public function setEnabledAttribute($value)
    {
        if (Admin::isAdmin() && $this->exists == true && $this->getKey() === auth()->guard($this->guard)->user()->getKey() && $this->enabled != $value) {
            return Admin::push('errors.request', 'Nie je možné deaktivovať vlastný účet.');
        }

        $this->attributes['enabled'] = $value;
    }

    /*
     * Disabling for change own permissions value
     */
    public function setPermissionsAttribute($value)
    {
        if (Admin::isAdmin() && $this->exists == true && $this->getKey() === auth()->guard($this->guard)->user()->getKey() && $this->permissions != $value) {
            return Admin::push('errors.request', 'Nie je možné upravovať vlastne administrátorske práva.');
        }

        $this->attributes['permissions'] = $value;
    }

    public function getAdminUser()
    {
        if ($this->avatar) {
            $this->avatar = $this->avatar->resize(100, 100)->url;
        }

        if ($this->canApplyUserRoles()) {
            $this->load('roles');
        }

        return $this->getAttributes() + $this->relationsToArray();
    }

    /*
     * Add columns
     */
    public function onMigrateEnd($table, $schema)
    {
        //Add remember token into user table
        if (! $schema->hasColumn($this->getTable(), 'remember_token')) {
            $column = $table->rememberToken();

            //add remember token after this columns
            if ($schema->hasColumn($this->getTable(), 'deleted_at')) {
                $column->before('deleted_at');
            } elseif ($schema->hasColumn($this->getTable(), 'avatar')) {
                $column->after('avatar');
            }
        }
    }

    /*
     * Check if model can apply user roles
     */
    public function canApplyUserRoles()
    {
        if (! Admin::isRolesEnabled()) {
            return false;
        }

        return class_exists('\App\User') && ($this instanceof \App\User) || $this->withUserRoles == true;
    }

    /*
     * If is logged user with super admin access
     */
    public function hasAllowedRole()
    {
        return ($admin = admin()) && (
            admin()->hasAdminAccess() || admin()->hasAccess(Admin\Models\UsersRole::class, 'update')
        );
    }

    /*
     * Add additional fields
     */
    public function mutateFields($fields)
    {
        /*
         * If is enabled admin groups
         */
        if ($this->canApplyUserRoles()) {
            $fields->push([
                'permissions' => 'name:admin::admin.super-admin|type:checkbox|default:0|'.($this->hasAllowedRole() ? '' : 'hideFromForm'),
                'roles' => 'name:admin::admin.admin-group|belongsToMany:users_roles,name|canAdd|'.($this->hasAllowedRole() ? '' : 'hideFromForm'),
            ]);
        }
    }
}
