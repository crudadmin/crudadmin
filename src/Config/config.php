<?php

/*
|--------------------------------------------------------------------------
| Crud Admin configuration
|--------------------------------------------------------------------------
|
| This is general configuration file for CrudAdmin. Any other configuration
| settings you can find at https://docs.crudadmin.com/#/config or in additional
| config file /vendor/crudadmin/crudadmin/src/config/config_additional.php
|
*/

return [
    /*
     * Administration name
     */
    'name' => 'My Admin',

    /*
     * License key
     */
    'license_key' => 'campaign2019',

    /*
     * Administration groups names
     */
    'groups' => [
        'settings' => ['Nastavenia', 'fa-gear'],
    ],

    /*
     * Add multi language mutations support
     */
    'localization' => false,

    /*
     * Gettext support
     */
    'gettext' => false,
];
