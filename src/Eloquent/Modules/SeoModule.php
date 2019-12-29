<?php

namespace Admin\Eloquent\Modules;

use Admin\Core\Eloquent\AdminModelModule;
use Admin\Fields\Group;

class SeoModule implements AdminModelModule
{
    public function isActive($model)
    {
        return $model->getProperty('seo') === true;
    }

    public function mutateFields($fields)
    {
        $fields->push(Group::tab([
            'meta_title' => 'name:Titulok stránky',
            'meta_keywords' => 'name:Kľúčové slova',
            'meta_description' => 'name:Popis stránky|type:text|max:400',
            'meta_image' => 'name:Obrázky stránky|image|multiple',
        ])->add('hidden')->name('Meta tagy')->icon('fa-info'));
    }
}
