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
            Gettext::updateLanguage($row->slug, [
                $row->poedit_po ? $row->poedit_po->path : null,
                $row->poedit_mo ? $row->poedit_mo->path : null
            ]);
            Gettext::syncTranslates($row);
        }
    }

    public function onUpdate($row)
    {
        //Update gettext files...
        if ( config('admin.gettext') === true )
        {
            Gettext::renameLocale($row->original['slug'], $row->slug);
            Gettext::updateLanguage($row->slug, [
                $row->poedit_po ? $row->poedit_po->path : null,
                $row->poedit_mo ? $row->poedit_mo->path : null
            ]);
        }
    }

    /*
     * Change filename po mo files,
     * because .mo files need to be unique
     */
    public function setPoeditMoFilename($filename)
    {
        return date('d-m-Y-h-i-s') . '.mo';
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
            'poedit_mo' => 'name:admin::admin.languages-mo-name|type:file|max:1024|hidden|extensions:mo|required_with:poedit_po',
        ]);
    }
}