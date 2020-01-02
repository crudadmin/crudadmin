<?php

namespace Admin\Models;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Facades\Admin\Helpers\SEOService;

class RoutesSeo extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-24 17:17:22';

    /*
     * Template name
     */
    protected $name = 'SEO';

    protected $icon = 'fa-globe';

    protected $insertable = false;

    protected $publishable = false;

    protected $group = 'settings';

    protected $reversed = true;

    protected $sortable = false;

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
            // 'Url adresa' => Group::fields([
                'url' => 'name:Url adresa|index|disabled|required',
                // 'new_url' => 'name:Nová url adresa|index',
                'group' => 'name:Skupina|index|invisible',
            // ])->inline(),
            'Meta tagy' => Group::fields([
                'title' => 'name:Titulok stránky',
                'keywords' => 'name:Kľúčové slova',
                'description' => 'name:Popis stránky|type:text|max:400',
                'image' => 'name:Obrázky stránky|image|multiple',
            ]),
        ];
    }

    public function beforeInitialAdminRequest()
    {
        SEOService::rebuildTree();
    }

    protected $settings = [
        'title.update' => 'Upravujete adresu :url'
    ];
}