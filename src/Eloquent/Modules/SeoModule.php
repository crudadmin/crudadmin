<?php

namespace Admin\Eloquent\Modules;

use Admin\Core\Eloquent\Concerns\AdminModelModule;
use Admin\Core\Eloquent\Concerns\AdminModelModuleSupport;
use Admin\Fields\Group;

class SeoModule extends AdminModelModule implements AdminModelModuleSupport
{
    public function boot()
    {
        /*
         * Hide meta images from array
         */
        $this->getModel()->addHidden([
            'slug_dynamic',
            'meta_title',
            'meta_keywords',
            'meta_description',
            'meta_image',
        ]);
    }

    public function isActive($model)
    {
        return $model->getProperty('seo') === true;
    }

    public function mutateFields($fields, $row)
    {
        $model = $this->getModel();

        $items = [];

        //Add sluggable column settings
        if ( $model->hasSluggable() ) {
            $items = array_merge($items, [Group::fields([
                'slug' => 'name:Url adresa|readonlyIf:slug_dynamic,1|unique:'.$model->getTable().',slug,'.(isset($row) ? $row->getKey() : 'NULL').',id,deleted_at,NULL',
                'slug_dynamic' => 'name:Automatická url adresa|type:checkbox|default:1',
            ])->inline()->add('hidden')]);
        }

        $fields->push(Group::tab(array_merge($items, [
            'meta_title' => 'name:Titulok stránky',
            'meta_keywords' => 'name:Kľúčové slova',
            'meta_description' => 'name:Popis stránky|type:text|max:400',
            'meta_image' => 'name:Obrázky stránky|image|multiple',
        ]))->add('hidden')->name('Meta tagy')->icon('fa-info'));
    }
}
