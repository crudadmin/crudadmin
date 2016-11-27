<?php

namespace Gogol\Admin\Models;

use Illuminate\Notifications\Notifiable;
use Gogol\Admin\Models\Authenticatable;

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
     * Model Parent
     * Eg. Articles::class,
     */
    protected $belongsToModel = null;

    /*
     * Minimum page rows
     * Default = 0
     */
    protected $minimum = 0;

    /*
     * Maximum page rows
     * Default = 0 = ∞
     */
    protected $maximum = 0;

    /*
     * Enable sorting rows
     */
    protected $sortable = false;

    /*
     * Enable publishing rows
     */
    protected $publishable = false;

    /*
     * Skipping dropping columns
     */
    protected $skipDroppingColumn = true;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password
     * ... other validation methods from laravel
     */
    protected function fields($row)
    {
        return [
            'username' => 'name:Meno a priezvisko|placeholder:Zadajte meno a priezvisko administrátora|type:string|required|max:30',
            'email' => 'name:Email|placeholder:Zadajte email administrátora|type:string|email|required|max:30|unique:users,email,'.(isset($row) ? $row->getKey() : 'NULL').',id,deleted_at,NULL',
            'password' => 'name:Heslo|type:password|confirmed|min:4|max:30|'.( ! isset($row) ? 'required' : '' ),
            'permissions' => 'name:Administrátor|type:checkbox',
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
        ];
    }

    public function hasAdminAccess()
    {
        return $this->permissions === 1;
    }
}