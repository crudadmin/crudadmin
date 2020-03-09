<?php

namespace Admin\Models;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Admin;

class StaticImage extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2020-03-08 12:20:22';

    /*
     * Template name
     */
    protected $name = 'Statické obrázky na webe';

    protected $insertable = false;

    protected $publishable = false;

    protected $sortable = false;

    protected $active = false;

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'key' => 'name:Image key|index',
            'image' => 'name:Obrázok|image',
            'alt' => 'name:Alt|'.(Admin::isEnabledLocalization() ? 'locale' : ''),
        ];
    }

    public function setModelPermissions($permissions)
    {
        $permissions['update'] = [
            'title' => _('Pri prechádzani webu bude môcť prihláseny administrátor upravovať statické obrázky pomocou upravovateľského módu.'),
        ];

        return $permissions;
    }
}