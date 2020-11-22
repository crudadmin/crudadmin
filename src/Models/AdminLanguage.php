<?php

namespace Admin\Models;

use Admin\Admin\Rules\CanDeleteDefaultAdminLanguage;
use Admin\Eloquent\Concerns\Gettextable;
use Admin\Helpers\File;
use Admin\Helpers\Localization\AdminResourcesSyncer;
use Admin\Helpers\Localization\ResourcesGettext;

class AdminLanguage extends Model
{
    use Gettextable;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2016-06-05 01:00:00';

    /*
     * Template name
     */
    protected $name = 'Preklady administrácie';

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
    public $gettextDirectory = 'gettext_admin';

    protected $rules = [
        CanDeleteDefaultAdminLanguage::class,
    ];

    /*
     * Check if this module is enabled for this user
     */
    public function active()
    {
        return admin() && admin()->hasAdminAccess();
    }

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
     * From this files will be loaded all translates
     */
    public function sourcePaths()
    {
        return config('admin.gettext_admin_source_paths', []);
    }

    /**
     * We does not want files paths in gettext poedit files
     *
     * @return  void
     */
    public function loadGettextFilesWithReferences()
    {
        return false;
    }

    public function getPoPath()
    {
        if ( ! $this->poedit_po ) {
            return new File((new ResourcesGettext)->getPoPath($this->slug));
        }

        return $this->poedit_po;
    }

    public function beforeGettextFilesSync()
    {
        //Switch gettext localization
        (new AdminResourcesSyncer)->syncModelTranslations();
    }
}
