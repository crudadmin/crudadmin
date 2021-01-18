<?php

namespace Admin\Models;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\Concerns\ModelUsersRoles;
use DB;

class UsersRole extends AdminModel
{
    use ModelUsersRoles;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2016-07-09 18:12:00';

    /*
     * Template name
     */
    protected $name = 'Skupiny právomoci';

    /*
     * Template title
     * Default ''
     */
    protected $title = 'admin::admin.user-groups-title';

    /*
     * Group
     */
    protected $group = 'settings';

    /*
     * Disabled publishing
     */
    protected $publishable = false;

    /*
     * Disabled sorting
     */
    protected $sortable = false;

    /*
     * Model icon
     */
    protected $icon = 'fa-universal-access';

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/data/checkbox
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'name' => 'name:admin::admin.user-groups-name|placeholder:admin::admin.user-groups-placeholder|type:string|required|max:90',
            'permissions' => 'name:admin::admin.user-groups-modules|type:json|component:UsersRolesRestriction',
            'default_model' => 'name:Predvolený modul|title:Tento modul bude spústený po prihlásení sa do systému|type:select',
        ];
    }

    public function settings()
    {
        return [
            'buttons.create' => _('Nová skupina'),
            'title.create' => _('Nová používateľska skupina'),
            'title.update' => _('Upravujete skupinu č. :id'),
            'grid.default' => 'small',
            'grid.big.disabled' => true,
        ];
    }

    public function options()
    {
        return [
            'default_model' => $this->getDefaultModels(),
        ];
    }

    public function onTableCreate()
    {
        //When roles table is created, set all users as super admins.
        DB::table('users')->update(['permissions' => 1]);
    }

    /*
     * Update permissions titles
     */
    public function setModelPermissions($permissions)
    {
        $permissions['insert']['danger'] = true;

        $permissions['update']['title'] = _('Administrátor v tejto skupine môže nadobudnúť plný prístup k systému, keďže môže zmeniť právomoci akejkoľvek skupine.');
        $permissions['update']['danger'] = true;
        $permissions['all']['title'] = $permissions['update']['title'];

        return $permissions;
    }

    public function setPermissionsAttribute($value)
    {
        $models = json_decode($value ?: '[]', true);

        foreach ($models as $namespace => $permissions) {
            //Remove class which does not exists anymore
            if ( !class_exists($namespace) ){
                unset($models[$namespace]);
            }

            $model = new $namespace;

            //Whitelist only allowed permissions for given user and model
            $permissions = array_intersect_key($permissions, $model->getModelPermissions());

            $models[$namespace] = $permissions;
        }

        $this->attributes['permissions'] = json_encode(json_encode($models));
    }
}
