<?php

namespace Admin\Models;

use Admin\Admin\Buttons\LogoutUser;
use Admin\Eloquent\Authenticatable;
use Admin\Fields\Group;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

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

    /**
     * Buttons
     *
     * @var  array
     */
    protected $buttons = [
        LogoutUser::class,
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
            'email' => 'name:Email|placeholder:Zadajte email administrátora|type:string|email|required|max:60|unique:users,email,'.(isset($row) ? $row->getKey() : 'NULL').',id,deleted_at,NULL',
            'password' => 'name:Heslo|type:password|confirmed|min:4|max:40|'.(isset($row) ? '' : '|required'),
            'avatar' => 'name:Profilová fotografia|type:file|image',
            Group::fields([
                'enabled' => 'name:Aktívny|type:checkbox|default:1',
            ])->inline(),
        ];
    }
}
