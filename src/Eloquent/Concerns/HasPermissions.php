<?php

namespace Admin\Eloquent\Concerns;

use Admin\Eloquent\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Admin;

trait HasPermissions
{
    /*
     * We want disable adminRows infinity loop
     */
    public static $adminPermissionsInUse = false;

    /**
     * If user is assigned to some object eg. via belongsTo column,
     * then we can filter all other child models which belongs to same model as given user.
     *
     * @return  array
     */
    public function filterRowsByColumns()
    {
        return [];
    }

    /**
     * Returns model permissions for admin roles
     *
     * @return  array
     */
    public function defaultModelPermissions()
    {
        //Inactive model does not have any default permissions
        if ( $this->getProperty('active') === false ){
            return [];
        }

        $permissions = [
            'read' => [
                'name' => trans('admin::admin.roles-read'),
                'title' => null,
                'danger' => false,
            ],
        ];

        if ( $this->insertable ) {
            $permissions['insert'] = [
                'name' => trans('admin::admin.roles-insert'),
                'title' => null,
                'danger' => false,
            ];
        }


        $permissions['update'] = [
            'name' => trans('admin::admin.roles-update'),
            'title' => null,
            'danger' => false,
        ];

        if ( $this->publishable ) {
            $permissions['publishable'] = [
                'name' => trans('admin::admin.roles-publishable'),
                'title' => null,
                'danger' => false,
            ];
        }

        if ( $this->deletable ) {
            $permissions['delete'] = [
                'name' => trans('admin::admin.roles-delete'),
                'title' => null,
                'danger' => false,
            ];
        }

        return $permissions;
    }

    /**
     * Returns all model permissions
     *
     * @return  array
     */
    public function getModelPermissions()
    {
        $permissions = $this->defaultModelPermissions();

        return $this->setModelPermissions($permissions);
    }

    public function hasFileAccess($fieldKey)
    {
        if ( $this->isPrivateFile($fieldKey) === false ){
            return true;
        }

        if ( !admin() ){
            return false;
        }

        if ( admin()->hasAccess($this, 'read') === false ){
            return false;
        }

        return true;
    }

    public function canViewAllRowsAccordingToLoggedUser()
    {
        if ( !($this instanceof Authenticatable) ){
            return true;
        }

        if ( !admin() ){
            return false;
        }

        //User is logged under another admin model
        if ( $this->getTable() != admin()->getTable() ){
            return true;
        }

        //User has view all access
        if ( admin()->hasAccess($this::class, 'view_others') === true ){
            return true;
        }

        return false;
    }

    public function withAdminPermissions()
    {
        static::addGlobalScope('adminPermissions', function(Builder $builder){
            //We want disable inherance of adminRows in this function. Because adminRows are global scope
            //It can be applied also on models inside this feature. And this will couase buggy behaoviour in
            //relations auto-finding.
            if ( static::$adminPermissionsInUse ){
                return;
            }

            static::$adminPermissionsInUse = true;

            //Check if user can see other rows than current session permissions
            $builder->filterByPermissions();

            static::$adminPermissionsInUse = false;
        });

        return $this;
    }

    /**
     * Filter rows by logged admin user permissions
     */
    public function scopeFilterByPermissions($query)
    {
        //Check if user has been logged (we can't use admin() causing authentication loop)
        if ( Admin::getAdminGuard()?->hasUser() && $this->canViewAllRowsAccordingToLoggedUser() == false ) {
            $query->where($this->qualifyColumn('id'), admin()->getKey());
        }

        $query->filterByRelatedModels();
    }

    /**
     * Filter given model by logged user relationship
     */
    protected function scopeFilterByRelatedModels($query)
    {
        //Check if user has been logged (we can't use admin() causing authentication loop)
        if ( !Admin::getAdminGuard()?->hasUser() ){
            return;
        }

        $admin = admin();

        foreach ($admin->filterRowsByColumns() as $filterByKey) {
            $permissionTable = $this->getRelatedPermissionTable($admin, $filterByKey);

            $filterBy = $admin->{$filterByKey};

            $rules = [];

            //Filter by exact model table
            if ( $permissionTable == $this->getTable() ){
                $rules['id'] = $filterBy;
            }

            // Filter by belongsToModel relations
            foreach ($this->getBelongsToRelation() as $relatedModel) {
                $relatedModel = new $relatedModel;

                if ( $relatedModel->getTable() == $permissionTable ){
                    $rules[$this->getForeignColumn($relatedModel->getTable())] = $filterBy;
                }
            }

            // Filter by field with hasAccessFilter param
            // TODO: Once it is $admin->getRelationProperty, and for belongsToMany it is $this->getRelationProperty, find out why and refactor.
            foreach ($this->getFields() as $key => $field) {
                // belongsTo relation filters
                if ( ($field['belongsTo'] ?? false) && $this->hasFieldParam($key, 'hasAccessFilter', true)){
                    $params = $admin->getRelationProperty($key, 'belongsTo');

                    if ( $params[0] == $permissionTable ) {
                        $rules[$key] = $filterBy;
                    }
                }

                // Support belongs to many relations filters
                if ( ($field['belongsToMany'] ?? false) && $this->hasFieldParam($key, 'hasAccessFilter', true)){
                    $params = $this->getRelationProperty($key, 'belongsToMany');

                    if ( $params[0] == $permissionTable ) {
                        $query->whereHas($key, function ($query) use ($filterBy) {
                            $query->where($query->qualifyColumn('id'), $filterBy);
                        });
                    }
                }
            }

            // Filter by matched rules
            foreach ($rules as $key => $value) {
                $query->where($this->qualifyColumn($key), $value);
            }
        }
    }

    /**
     * Returns related table of permissions filter
     */
    private function getRelatedPermissionTable($admin, $key)
    {
        if ( $key == 'id' ){
            return $admin->getTable();
        }

        //Get relation table name from belongsTo field
        if ( $admin->hasFieldParam($key, 'belongsTo') ) {
            $field = $admin->getRelationProperty($key, 'belongsTo');

            return $field[0];
        }

        //Get relation table name from belongsToModel relation
        else {
            foreach ($admin->getBelongsToRelation() as $relation) {
                $table = (new $relation)->getTable();

                if ( $admin->getForeignColumn($table) == $key ){
                    return $table;
                }
            }
        }
    }
}