<?php

namespace Admin\Models;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Facades\Admin\Helpers\SEOService;
use Admin;

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
                'url' => 'name:admin::admin.seoroutes-url|index|disabled|required',
                // 'new_url' => 'name:admin::admin.seoroutes-newurl|index',
                'group' => 'name:admin::admin.seoroutes-group|index|invisible',
            // ])->inline(),
            'Meta tagy' => Group::fields([
                'title' => 'name:admin::admin.seoroutes-title'.(Admin::isEnabledLocalization() ? '|locale' : ''),
                'keywords' => 'name:admin::admin.seoroutes-keywords'.(Admin::isEnabledLocalization() ? '|locale' : ''),
                'description' => 'name:admin::admin.seoroutes-description|type:text|max:400'.(Admin::isEnabledLocalization() ? '|locale' : ''),
                'image' => 'name:admin::admin.seoroutes-images|image|multiple',
            ]),
        ];
    }

    public function beforeInitialAdminRequest()
    {
        SEOService::rebuildTree();
    }

    public function settings()
    {
        return [
            'dates' => false,
            'title.update' => ':url',
        ];
    }
}