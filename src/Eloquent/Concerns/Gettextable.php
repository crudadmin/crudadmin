<?php

namespace Admin\Eloquent\Concerns;

use Ajax;
use Admin;
use Gettext;

trait Gettextable
{
    public function settings()
    {
        return [
            'dates' => false,
            'title.insert' => trans('admin::admin.languages-add-new'),
            'title.update' => trans('admin::admin.languages-update'),
            'columns.downloadpo.name' => _('Súbory s prekladmi'),
            'columns.downloadpo.encode' => false,
            'fields.poedit_po.canDownload' => false,
            'fields.poedit_po.canDelete' => false,
        ];
    }

    /*
     * Is gettext support allowed
     */
    public function hasGettextSupport()
    {
        return true;
    }

    public function onCreate($row)
    {
        //Update gettext files...
        if ($this->hasGettextSupport()) {
            Gettext::createLocale($row->slug);

            Gettext::syncTranslates($row);

            Gettext::generateMoFiles($row->slug, $row);
        }
    }

    public function onUpdate($row)
    {
        //Update gettext files...
        if ($this->hasGettextSupport()) {
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
        if ($this->hasGettextSupport()) {
            $this->attributes['poedit_mo'] = date('d-m-Y-h-i-s').'.mo';
        }

        return $filename;
    }

    /*
     * Set slug
     */
    public function setSlugAttribute($value)
    {
        $slug = str_slug($value);

        if (strlen(str_replace('-', '', $slug)) != 2) {
            Ajax::error(_('Zadali skratku jazyka v nesprávnom formáte.'));
        }

        if (! $this->exists) {
            $this->attributes['slug'] = $slug;
        } elseif ($this->original['slug'] != $value) {
            Admin::push('errors', _('Skratku jazyka nie je možné po jej vytvorení premenovať.'));
        }
    }

    /*
     * Add additional conditional fields
     */
    public function mutateFields($fields)
    {
        /*
         * Checks for gettext support
         */
        if (config('admin.gettext') !== true) {
            return;
        }

        $fields->push([
            'poedit_po' => 'name:admin::admin.languages-po-name|type:file|max:1024|extensions:po|hidden',
            'poedit_mo' => 'name:admin::admin.languages-mo-name|type:string|max:30|invisible',
        ]);
    }

    /*
     * Add empty rows
     */
    public function onMigrateEnd($table, $schema)
    {
        if ($this->withUnpublished()->count() == 0) {
            $isLanguageTableSortable = Admin::getModelByTable($this->getTable())->isSortable();

            $languages = [
                ['name' => 'Slovenský', 'slug' => 'sk'] + ($isLanguageTableSortable ? ['_order' => 0] : []),
                ['name' => 'Anglický', 'slug' => 'en'] + ($isLanguageTableSortable ? ['_order' => 1] : []),
            ];

            $this->insert($languages);
        }
    }

    public function setAdminAttributes($attributes)
    {
        $url = action('\Admin\Controllers\GettextController@downloadTranslations', [$this->getKey(), $this->getTable()]);

        $attributes['downloadpo'] = '<a href="'.$url.'" target="_blank">'._('Stiahnuť súbor s prekladmi').'</a>';

        return $attributes;
    }
}
