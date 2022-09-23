<?php

namespace Admin\Eloquent\Concerns;

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
}