<?php

namespace Admin\Eloquent\Concerns;

use Admin\Eloquent\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

trait HasPermissions
{
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

    public function scopeFilterByPermissions($query)
    {
        if ( $this->canViewAllRowsAccordingToLoggedUser() == true ) {
            return;
        }

        $query->where($this->qualifyColumn('id'), admin()->getKey());
    }

    public function withAdminPermissions()
    {
        self::addGlobalScope('adminPermissions', function(Builder $builder){
            //Check if user can see other rows than current session permissions
            $builder->filterByPermissions();
        });

        return $this;
    }
}