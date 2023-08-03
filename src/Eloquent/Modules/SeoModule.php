<?php

namespace Admin\Eloquent\Modules;

use Admin\Core\Eloquent\Concerns\AdminModelModule;
use Admin\Core\Eloquent\Concerns\AdminModelModuleSupport;
use Admin\Fields\Group;
use Illuminate\Validation\Rule;
use Admin;

class SeoModule extends AdminModelModule implements AdminModelModuleSupport
{
    /*
     * This may be accessed from application API...
     */
    public static $metaKeys = [
        'meta_title',
        'meta_keywords',
        'meta_description',
        'meta_image',
    ];

    public function boot()
    {
        /*
         * Hide meta images from array
         */
        if (
            Admin::isFrontend()
            && $this->getModel()->getProperty('seoVisible') === false
        ) {
            $this->getModel()->makeHidden(array_merge([
                'slug_dynamic',
            ], self::$metaKeys));
        }
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
                'slug' => 'name:Url adresa|readonlyIf:slug_dynamic,1',
                'slug_dynamic' => 'name:Automatická url adresa|type:checkbox|default:1',
            ])->inline()->add('hidden')]);
        }

        $seoTab = Group::tab(array_merge($items, [
            'meta_title' => 'name:Titulok stránky'.(Admin::isEnabledLocalization() ? '|locale' : ''),
            'meta_keywords' => 'name:Kľúčové slova'.(Admin::isEnabledLocalization() ? '|locale' : ''),
            'meta_description' => 'name:Popis stránky|type:text|max:400'.(Admin::isEnabledLocalization() ? '|locale' : ''),
            'meta_canonical_url' => 'name:Kanonická url|url',
            'meta_image' => 'name:Obrázky stránky|image|multiple',
        ]))->add('hidden')->name('Meta tagy')->icon('fa-info')->id('seo_tab');

        $fields->push($seoTab);
    }

    /*
     * When all fields are already initialized, we can slightly mutate their parameters in this state.
     */
    public function mutateBootedFields(&$fields, $row, $model)
    {
        if ( $model->hasSluggable() ){
            $isLocalized = @$fields[$model->getProperty('sluggable')]['locale'] ?: false;

            //If sluggable column has locale attribute, we need add this attribute also into slug column
            if ( $model->hasSluggable() && $isLocalized ) {
                $fields['slug']['locale'] = true;
            }

            //Use json unique, or basic column unique method
            if ( $model->getProperty('slugUnique') !== false ){
                $fields['slug'][$isLocalized ? 'unique_json' : 'unique'] = $model->getTable().',slug,'.(isset($row) ? $row->getKey() : 'NULL').',id,deleted_at,NULL';
            }
        }

        return $fields;
    }
}
