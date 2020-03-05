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
    protected $name = 'admin::admin.user-groups';

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
        ];
    }

    protected $settings = [
        'grid.default' => 'small',
        'grid.big.disabled' => true,
    ];

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
        $permissions['update']['title'] = _('Administrátor v tejto skupine môže nadobudnúť plný prístup k systému, keďže môže zmeniť právomoci akejkoľvek skupine a administrátorom.');
        $permissions['update']['danger'] = true;
        $permissions['all']['title'] = $permissions['update']['title'];

        return $permissions;
    }
}
