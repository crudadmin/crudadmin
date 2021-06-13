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
            'increments' => false,
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
            Gettext::setGettextPropertiesModel($this);
        }
    }

    public function onUpdate($row)
    {
        //Update gettext files...
        if ($this->hasGettextSupport()) {
            Gettext::setGettextPropertiesModel($this);

            $locale = Gettext::getLocale($row->slug);
            $localePoPath = Gettext::getLocalePath($locale, $locale.'.po');

            //On update we need downloads file from cloud storage and save it into local storage
            Gettext::getStorage()->put(
                $localePoPath,
                $row->poedit_po->getStorage()->get($row->poedit_po->path)
            );

            //We can regenerate mo files on update
            Gettext::generateMoFile($row->slug, $localePoPath);
        }
    }

    /*
     * Set slug
     */
    public function setSlugAttribute($value)
    {
        $slug = str_slug($value);

        if (request()->expectsJson() && strlen(str_replace('-', '', $slug)) != 2) {
            Ajax::error(_('Zadali skratku jazyka v nesprávnom formáte.'));
        }

        if (! $this->exists) {
            $this->attributes['slug'] = $slug;
        } elseif ($this->original['slug'] != $value && request()->expectsJson()) {
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
        if ( $this->hasGettextSupport() ) {
            $fields->push([
                'poedit_po' => 'name:admin::admin.languages-po-name|type:file|max:1024|hasNotAccess:languages.update,invisible|extensions:po|hidden',
            ]);
        }

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

    /*
     * Download pofile
     */
    public function setAdminAttributes($attributes)
    {
        $url = action('\Admin\Controllers\GettextController@downloadTranslations', [$this->getKey(), $this->getTable()]);

        $attributes['downloadpo'] = '<a href="'.$url.'" target="_blank">'._('Stiahnuť súbor s prekladmi').'</a>';

        return $attributes;
    }

    public function setModelPermissions($permissions)
    {
        $permissions['update']['title'] = _('Administrátor bude môcť na webe taktiež spravovať všetky texty pomocou upravovateľského módu.');

        return $permissions;
    }

    public function getLocalPoPath()
    {
        Gettext::setGettextPropertiesModel($this);

        $locale = Gettext::getLocale($this->slug);

        $path = Gettext::getLocalePath($locale, $locale.'.po');

        return $path;
    }
}
