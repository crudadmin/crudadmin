<?php

namespace Admin\Eloquent\Concerns\Auth;

use Admin;
use Admin\Models\AdminsRole;

trait HasAdminRoles
{
    /*
     * Check if model can apply user roles
     */
    public function hasAdminRoles()
    {
        if (! Admin::isRolesEnabled()) {
            return false;
        }

        return true;
    }

    /*
     * Check if is user super administrator with all permissions
     */
    public function hasAdminAccess()
    {
        //Enable full access only for admin table
        if ( $this->getTable() == Admin::getAuthModel()->getTable() ){
            //Permissions are turned off
            if ( $this->hasAdminRoles() == false ){
                return true;
            }

            //Has root/admin permissions
            if ( $this->permissions === true ){
                return true;
            }
        }

        return false;
    }

    public function roles()
    {
        //Use roles assigned in CMS
        if ( $this->hasAdminRoles() ){
            return parent::roles();
        }

        if ( method_exists($this, 'enabledRoles') ) {
            $enabledRoles = $this->enabledRoles();
        } else {
            $enabledRoles = $this->enabledRoles ?: [];
        }

        //Use staticaly assigned roles
        return $this->hasMany(AdminsRole::class)->setQuery(
            AdminsRole::whereIn('id', $enabledRoles ?: [])->getQuery()
        );
    }

    /*
     * Get all allowed models from all groups which user owns
     */
    public function getUserPermissions()
    {
        if (Admin::isRolesEnabled() === false) {
            return [];
        }

        $key = 'users.'.$this->getTable().'.'.$this->getKey().'.permissions';

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
                return $this->hasFullAccessToModel($permissions, $model);
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
    private function hasFullAccessToModel($permissions, $model)
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
     * Update permissions titles
     */
    public function setModelPermissions($permissions)
    {
        //Add full access changing support
        if ( $this->hasAdminRoles() ) {
            $permissions['full_access'] = [
                'name' => _('Nastavenie plného prístupu'),
                'title' => $this->getFullAccessMessage().' '._('Keďže môže nastaviť ktorémukoľvek účtu plný prístup do administrácie.'),
                'danger' => true,
            ];

            //Add full access changing support
            $permissions['roles'] = [
                'name' => _('Priradenie skupín'),
                'title' => $this->getFullAccessMessage().' '._('Keďže môže zmeniť priradenie rol pre konkrétnych užívateľov.'),
                'danger' => true,
            ];
        }

        if ( $this->hasAutoLogoutSupport() ) {
            $permissions['logout'] = [
                'name' => _('Odhlásenie používateľov'),
                'danger' => false,
            ];
        }

        $permissions['view_others'] = [
            'name' => _('Zobrazenie ostatných používateľov'),
            'title' => $this->getFullAccessMessage().' '._('Taktiež môže zmeniť prihlasovacie údaje ktorémukoľvek používateľovi.'),
            'danger' => true,
        ];

        //Update title for all
        $permissions['all']['title'] = $permissions['update']['title'];

        return $permissions;
    }

    protected function getFullAccessMessage()
    {
        return _('Používateľ v tejto skupine môže nadobudnúť plný prístup k systému.');
    }
}
