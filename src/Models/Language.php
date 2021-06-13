<?php

namespace Admin\Models;

use Admin\Eloquent\Concerns\Gettextable;

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

    /*
     * Where will be located po/mo files in storage lang directory
     */
    public $gettextDirectory = 'gettext';

    /*
     * Automatic form and database generation
     */
    protected function fields($row)
    {
        return [
            'name' => 'name:admin::admin.languages-name|placeholder:admin::admin.languages-title|required|max:25',
            'slug' => 'name:admin::admin.languages-prefix|placeholder:admin::admin.languages-prefix-title|required|size:2|unique:languages,slug,'.(isset($row) ? $row->getKey() : 'NULL').',id,deleted_at,NULL',
        ];
    }

    /*
     * Returns if model has gettext support
     */
    public function hasGettextSupport()
    {
        return config('admin.gettext') === true;
    }

    /*
     * From this files will be loaded all translates
     */
    public function sourcePaths()
    {
        return config('admin.gettext_source_paths', []);
    }

    public function loadGettextFilesWithReferences()
    {
        return true;
    }
}
