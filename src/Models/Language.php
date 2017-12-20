<?php

namespace Gogol\Admin\Models;

use Gogol\Admin\Models\Model;
use Gogol\Admin\Traits\Gettextable;

class Language extends Model
{
    use Gettextable;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2016-06-05 00:00:00';

    /*
     * Template name
     */
    protected $name = 'admin::admin.languages';

    /*
     * Template title
     * Default ''
     */
    protected $title = 'admin::admin.languages-title';

    /*
     * Group
     */
    protected $group = 'settings';

    /*
     * Acivate/deactivate model in administration
     */
    protected $active = true;

    /*
     * Minimum page rows
     * Default = 0
     */
    protected $minimum = 1;

    /*
     * Reversed rows
     */
    protected $reversed = true;

    /*
     * Disable deleting old files
     */
    protected $delete_files = false;

    public function settings()
    {
        return [
            'title.insert' => trans('admin::admin.languages-add-new'),
            'title.update' => trans('admin::admin.languages-update'),
        ];
    }

    /*
     * Automatic form and database generation
     */
    protected function fields($row)
    {
        $rules = [
            'name' => 'name:admin::admin.languages-name|placeholder:admin::admin.languages-title|required|max:25',
            'slug' => 'name:admin::admin.languages-prefix|placeholder:admin::admin.languages-prefix-title|required|size:2|unique:languages,slug,'.(isset($row) ? $row->getKey() : 'NULL').',id,deleted_at,NULL',
        ];

        /*
         * Checks for gettext support
         */
        if ( config('admin.gettext') === true )
        {
            $rules['poedit_po'] = 'name:admin::admin.languages-po-name|type:file|max:1024|extensions:po|required_with:poedit_mo';
            $rules['poedit_mo'] = 'name:admin::admin.languages-mo-name|type:file|max:1024|hidden|extensions:mo|required_with:poedit_po';
        }

        return $rules;
    }
}