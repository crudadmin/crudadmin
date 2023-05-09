<?php

namespace Admin\Eloquent;

use Admin;
use Admin\Eloquent\Concerns\CanResetPassword;
use Admin\Eloquent\Concerns\HasAutoLogoutTrait;
use Admin\Eloquent\Concerns\HasLoginVerificator;
use Admin\Eloquent\Modules\VerificationModule;
use Illuminate\Auth\Authenticatable as BaseAuthenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;

class Authenticatable extends AdminModel implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use BaseAuthenticatable, Authorizable, CanResetPassword, Notifiable, MustVerifyEmail, HasAutoLogoutTrait, HasLoginVerificator;

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
    protected $guard = null;

    /*
     * Add Admin rules permissions
     */
    protected $withUserRoles = false;

    public function __construct(array $attributes = [])
    {
        if (Admin::isFrontend()) {
            $this->publishable = false;
        }

        if ( $this->isAdminUserClass() ) {
            $this->addModule(VerificationModule::class);
        }

        parent::__construct($attributes);
    }

    public function getGuard()
    {
        if ( $this->guard ){
            return auth()->guard($this->guard);
        }

        return Admin::getAdminGuard();
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
        return Admin::cache($key, function(){
            $models = [];

            if ($admin_groups = $this->roles) {
                foreach ($admin_groups as $group) {
                    //JSON decode is backward support for old crudadmin versions (4.1/3)
                    $permissions = is_string($group->permissions) ? (array) json_decode($group->permissions ?: '{}', true) : $group->permissions;

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

            return $models;
        });
    }

    /**
     * Check if has user allowed model by namespace
     *
     * @param  string  $model classpath... Eg: \App\User
     * @param  bool|string  $permissionKey (if string is given, will check specific permission type). If true is given, will check if has at least one permission type.
     * @return  bool
     */
    public function hasAccess($model, $permissionKey = true)
    {
        //If roles are not enabled, allow everything
        //Or if is user type set as SuperAdmin
        if (Admin::isRolesEnabled() === false || $this->hasAdminAccess()) {
            return true;
        }

        if (is_object($model)) {
            $model = get_class($model);
        } else {
            $model = trim($model, '/');
        }

        //Check specific role ID
        if ( is_numeric($permissionKey) ) {
            return $this->roles->where('id', $permissionKey)->count() > 0;
        }

        //Check available permission rule from every role
        else {
            $permissions = $this->getUserPermissions();

            //Check if any permission is present
            if ( $permissionKey === true ) {
                //Check if has at least one true permission
                if ( array_key_exists($model, $permissions) && count(array_keys($permissions[$model])) > 0 ) {
                    return true;
                }

                return false;
            }

            //Full table access (we need use ===, because true == '*')
            else if ( $permissionKey === '*' ){
                return $this->hassFullAccessToModel($permissions, $model);
            }

            return array_key_exists($model, $permissions) && @$permissions[$model][$permissionKey] === true;
        }
    }

    /**
     * Check if user has all available permissions to given table
     *
     * @param  array  $permissions
     * @param  string  $model
     *
     * @return  bool
     */
    private function hassFullAccessToModel($permissions, $model)
    {
        //We need retrieve model without booting
        $foundModel = array_values(array_filter(Admin::getAdminModels(), function($item) use ($model) {
            return get_class($item) == $model;
        }));

        //If user does not have any permissions for given model, or model has not been found
        if ( count($foundModel) === false || !array_key_exists($model, $permissions) ){
            return false;
        }

        $allModelPermissionsKeys = array_keys($foundModel[0]->getModelPermissions());

        foreach ($allModelPermissionsKeys as $key) {
            if ( @$permissions[$model][$key] !== true ){
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has access to model by table name
     *
     * @return  bool
     */
    public function hasAccessByTable($table, $permissionKey = null)
    {
        $classname = get_class(Admin::getModelByTable($table));

        return $this->hasAccess($classname, $permissionKey);
    }

    /*
     * Check if is user super administrator with all permissions
     */
    public function hasAdminAccess()
    {
        return Admin::isRolesEnabled() === false || $this->permissions == 1;
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
        if (Admin::isAdmin() && $this->exists == true && $this->getKey() === $this->getGuard()->user()->getKey() && $this->enabled != $value) {
            return Admin::push('errors.request', 'Nie je možné deaktivovať vlastný účet.');
        }

        $this->attributes['enabled'] = $value;
    }

    /*
     * Disabling for change own permissions value
     */
    public function setPermissionsAttribute($value)
    {
        if (Admin::isAdmin() && $this->exists == true && $this->getKey() === $this->getGuard()->user()->getKey() && $this->permissions != $value) {
            return Admin::push('errors.request', 'Nie je možné upravovať vlastne administrátorske práva.');
        }

        $this->attributes['permissions'] = $value;
    }

    public function getAvatarThumbnailAttribute()
    {
        return $this->avatar ? $this->avatar->resize(100, 100)->url : null;
    }

    public function setAdminResponse()
    {
        $this->append([
            'avatarThumbnail',
        ]);

        if ($this->canApplyUserRoles()) {
            $this->load('roles');
        }

        return $this;
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

    /**
     * Check if end class is user model
     *
     * @return  bool
     */
    public function isAdminUserClass()
    {
        return class_basename($this) == config('admin.auth_eloquent');
    }

    /*
     * Check if model can apply user roles
     */
    public function canApplyUserRoles()
    {
        if (! Admin::isRolesEnabled()) {
            return false;
        }

        return $this->isAdminUserClass() || $this->withUserRoles == true;
    }

    /*
     * Add additional fields
     */
    public function mutateFields($fields)
    {
        //If has logout user feature
        if ( $this->hasAutoLogoutSupport() ) {
            $fields->push([
                'logout_date' => 'name:Odhlásiť od dátumu|type:datetime|inaccessible',
            ]);
        }

        /*
         * If is enabled admin groups
         */
        if ($this->canApplyUserRoles()) {
            $fields->pushAfter('enabled', [
                'permissions' => 'name:admin::admin.super-admin|type:checkbox|default:0|tooltip:'.$this->getFullAccessMessage().'|hasNotAccess:full_access,invisible',
            ]);

            $fields->push([
                'roles' => 'name:admin::admin.admin-group|hideFromFormIf:permissions,1|belongsToMany:users_roles,name|canAdd|hasNotAccess:roles,invisible',
            ]);
        }

        /*
         * Added user language preference
         */
        if ( $this->isAdminUserClass() && Admin::isEnabledAdminLocalization() ){
            $fields->push([
                'language' => 'name:Predvolený jazyk|belongsTo:admin_languages,name|invisible'
            ]);
        }
    }

    private function getFullAccessMessage()
    {
        return _('Administrátor v tejto skupine môže nadobudnúť plný prístup k systému.');
    }

    /*
     * Update permissions titles
     */
    public function setModelPermissions($permissions)
    {
        //We does not want mutate titles in other than user model
        if ( $this->isAdminUserClass() == false ){
            return $permissions;
        }

        //Set alert tooltips when editing permission group
        $permissions['update']['title'] = $this->getFullAccessMessage().' '._('Taktiež môže zmeniť prihlasovacie údaje ktorémukoľvek administrátorovi.');
        $permissions['update']['danger'] = true;

        //Add full access changing support
        $permissions['full_access'] = [
            'name' => _('Nastavenie plného prístupu'),
            'title' => _('Administrátor v tejto skupine môže nadobudnúť plný prístup k systému, keďže môže nastaviť ktorémukoľvek účtu plný prístup do administrácie.'),
            'danger' => true,
        ];

        //Add full access changing support
        $permissions['roles'] = [
            'name' => _('Priradenie skupín'),
            'title' => _('Administrátor v tejto skupine môže nadobudnúť plný prístup k systému, keďže môže zmeniť priradenie rol pre konkrétnych užívateľov.'),
            'danger' => true,
        ];

        $permissions['logout'] = [
            'name' => _('Odhlásenie používateľov'),
            'danger' => false,
        ];

        //Update title for all
        $permissions['all']['title'] = $permissions['update']['title'];

        return $permissions;
    }
}
