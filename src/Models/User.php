<?php

namespace Gogol\Admin\Models;

use Gogol\Admin\Models\Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2016-07-09 17:27:57';

    /*
     * Template name
     */
    protected $name = 'Administrátori';

    /*
     * Template title
     * Default ''
     */
    protected $title = 'Upravte zoznam administrátorov';

    /*
     * Group
     */
    protected $group = 'settings';

    /*
     * Minimum page rows
     * Default = 0
     */
    protected $minimum = 1;

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password
     * ... other validation methods from laravel
     */
    protected function fields($row)
    {
        $fields = [
            'username' => 'name:Meno a priezvisko|placeholder:Zadajte meno a priezvisko administrátora|type:string|required|max:30',
            'email' => 'name:Email|placeholder:Zadajte email administrátora|type:string|email|required|max:30|unique:users,email,'.(isset($row) ? $row->getKey() : 'NULL').',id,deleted_at,NULL',
            'password' => 'name:Heslo|type:password|confirmed|min:4|max:30'.( ! isset($row) ? '|required' : '|nullable' ),
            'avatar' => [
                'name' => 'Profilová fotografia',
                'type' => 'file',
                'image' => true,
                'max' => 8024,

                //Postprocess image
                'resize' => [
                    [ 'fit' => [100] ], // thumbs directory
                ],
            ],
            'enabled' => 'name:Aktívny|type:checkbox|default:1',
        ];

        /*
         * If is enabled admin groups
         */
        if ( config('admin.admin_groups') === true )
        {
            $fields['permissions'] = 'name:Super administrátor|type:checkbox|default:0';
            $fields['admins_groups'] = 'name:Administrátorska skupina|belongsToMany:admins_groups,name';
        }

        return $fields;
    }
}