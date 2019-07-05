<?php

namespace Gogol\Admin\Traits;

use Gettext;
use Admin;
use Ajax;

trait Gettextable
{
    public function onCreate($row)
    {
        //Update gettext files...
        if ( config('admin.gettext') === true )
        {
            Gettext::createLocale($row->slug);

            Gettext::syncTranslates($row);

            Gettext::generateMoFiles($row->slug, $row);
        }
    }

    public function onUpdate($row)
    {
        //Update gettext files...
        if ( config('admin.gettext') === true )
        {
            Gettext::renameLocale($row->original['slug'], $row->slug);

            Gettext::generateMoFiles($row->slug, $row);
        }
    }

    /*
     * Change filename po mo files,
     * because .mo files need to be unique
     */
    public function setPoeditPoFilename($filename)
    {
        //Regenerate mo files from po files
        if ( config('admin.gettext') === true )
        {
            $this->attributes['poedit_mo'] = date('d-m-Y-h-i-s') . '.mo';
        }

        return $filename;
    }

    /*
     * Set slug
     */
    public function setSlugAttribute($value)
    {
        $slug = str_slug($value);

        if ( strlen(str_replace('-', '', $slug)) != 2 )
            Ajax::error('Zadali skratku jazyka v nesprávnom formáte.');

        if ( ! $this->exists )
            $this->attributes['slug'] = $slug;
        else if ( $this->original['slug'] != $value )
            Admin::push('errors', 'Skratku jazyka nie je možné po jej vytvorení premenovať.');
    }

    /*
     * Add additional conditional fields
     */
    public function mutateFields($fields)
    {
        /*
         * Checks for gettext support
         */
        if ( config('admin.gettext') !== true )
            return;

        $fields->push([
            'poedit_po' => 'name:admin::admin.languages-po-name|type:file|max:1024|extensions:po|required_with:poedit_mo',
            'poedit_mo' => 'name:admin::admin.languages-mo-name|type:string|max:30|invisible',
        ]);
    }

    /*
     * Add columns
     */
    public function onMigrate($table, $schema)
    {
        if ( $this->withUnpublished()->count() == 0 )
        {
            $isLanguageTableSortable = Admin::getModelByTable('languages')->isSortable();

            $languages = [
                [ 'name' => 'Slovenský', 'slug' => 'sk' ] + ($isLanguageTableSortable ? [ '_order' => 0 ] : []),
                [ 'name' => 'Anglický', 'slug' => 'en' ] + ($isLanguageTableSortable ? [ '_order' => 1 ] : []),
            ];

            $this->insert($languages);
        }
    }
}