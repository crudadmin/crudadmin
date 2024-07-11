<?php

namespace Admin\Eloquent\Concerns;

use Admin\Eloquent\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

trait HasPermissions
{
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
        self::addGlobalScope('adminPermissions', function(Builder $builder){
            //Check if user can see other rows than current session permissions
            $builder->filterByPermissions();
        });

        return $this;
    }

    /**
     * Filter rows by logged admin user permissions
     */
    public function scopeFilterByPermissions($query)
    {
        if ( $this->canViewAllRowsAccordingToLoggedUser() == false ) {
            $query->where($this->qualifyColumn('id'), admin()->getKey());
        }

        $query->filterByRelatedModels();
    }

    /**
     * Filter given model by logged user relationship
     */
    public function scopeFilterByRelatedModels($query)
    {
        $admin = admin();

        foreach ($admin->filterRowsByColumns() as $key) {
            //Get relation table name from belongsTo field
            if ( $admin->hasFieldParam($key, 'belongsTo') ) {
                $field = $admin->getRelationProperty($key, 'belongsTo');
                $permissionTable = $field[0];
            }

            //Get relation table name from belongsToModel relation
            else {
                foreach ($admin->getBelongsToRelation() as $relation) {
                    $table = (new $relation)->getTable();
                    if ( $admin->getForeignColumn($table) == $key ){
                        $permissionTable = $table;
                    }
                }
            }

            $filterBy = $admin->{$key};

            //Filter by exact model table
            if ( $permissionTable == $this->getTable() ){
                $query->where($this->qualifyColumn('id'), $filterBy);
            }

            //Filter by belongsToModel relations
            foreach ($this->getBelongsToRelation() as $relatedModel) {
                $relatedModel = new $relatedModel;

                if ( $relatedModel->getTable() == $permissionTable ){
                    $query->where($this->getForeignColumn($relatedModel->getTable()), $filterBy);
                }
            }
        }
    }
}