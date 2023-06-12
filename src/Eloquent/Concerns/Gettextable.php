<?php

namespace Admin\Eloquent\Concerns;

use Admin;
use Admin\Admin\Buttons\SetSourceLanguage;
use Gettext;

trait Gettextable
{
    public function getSourcePaths()
    {
        return $this->sourcePaths();
    }

    public function getGettextPath($path = null)
    {
        return ($this->gettextDirectory ?: 'app').'/'.$path;
    }

    public function getLocalePath($locale, $file = null)
    {
        return $this->getGettextPath($locale.'/LC_MESSAGES/'.$file);
    }

    public function getLocalePrefixAttribute()
    {
        return '';
    }

    public function getLocalePrefixWithSlashAttribute()
    {
        return ($this->localePrefix ? $this->localePrefix.'_' : '');
    }

    public function setRulesProperty($rules)
    {
        $rules[] = SetSourceLanguage::class;

        return $rules;
    }

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

    public function onUpdate($row)
    {
        //Update gettext files...
        if ($this->hasGettextSupport()) {
            //On update we need downloads file from cloud storage and save it into local storage
            if ( $row->poedit_po->exists ) {
                Gettext::getStorage()->put(
                    $this->localPoPath,
                    $row->poedit_po->get()
                );

                //We can regenerate mo files on update
                Gettext::generateMoFile($row);
            }
        }
    }

    /*
     * Set slug
     */
    public function setSlugAttribute($value)
    {
        $slug = str_slug($value);

        if (request()->expectsJson() && strlen(str_replace('-', '', $slug)) != 2) {
            autoAjax()->error(_('Zadali skratku jazyka v nesprávnom formáte.'))->throw();
        }

        if (! $this->exists) {
            $this->attributes['slug'] = $slug;
        } elseif ($this->original['slug'] != $value && request()->expectsJson()) {
            autoAjax()->pushMessage(_('Skratku jazyka nie je možné po jej vytvorení premenovať.'));
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
                'is_source' => 'name:Zdrojovy jazyk|type:checkbox|default:0',
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

    public function getLocalPoPathAttribute()
    {
        if ( $locale = $this->locale ) {
            $filename = $this->localePrefixWithSlash.$locale.'.po';

            $path = $this->getLocalePath($locale, $filename);
        }

        return $path;
    }

    public function getLocalPoBasePathAttribute()
    {
        return Gettext::getStorage()->path($this->localPoPath);
    }

    public function getLocaleAttribute()
    {
        return Gettext::getLocale($this->slug);
    }
}
