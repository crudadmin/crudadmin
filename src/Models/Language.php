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
    protected $name = 'Jazyková mutácia';

    /*
     * Template title
     * Default ''
     */
    protected $title = 'Upravte si nastavenia jazykových mutácii. Taktiež si nastavte predvolený jazyk umiestnením jazyka na prvú pozíciu.';

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

    /*
     * Automatic form and database generation
     */
    protected function fields($row)
    {
        $rules = [
            'name' => 'name:Názov jazyka|placeholder:Zadajte názov jazyka|required|max:25',
            'slug' => 'name:Skratka jazyka|placeholder:Zadajte skrátku jazyka (en, sk, de, cz)...|required|size:2|unique:languages,slug,'.(isset($row) ? $row->getKey() : 'NULL').',id,deleted_at,NULL',
        ];

        /*
         * Checks for gettext support
         */
        if ( config('admin.gettext') === true )
        {
            $rules['poedit_po'] = 'name:Preklad .po súboru pre poedit|type:file|max:1024|extensions:po|required_with:poedit_mo';
            $rules['poedit_mo'] = 'name:Preložený .mo súbor|type:file|max:1024|hidden|extensions:mo|required_with:poedit_po';
        }

        return $rules;
    }
}