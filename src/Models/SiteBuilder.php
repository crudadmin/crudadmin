<?php

namespace Admin\Models;

use Admin;
use Admin\Contracts\Sitebuilder\SiteBuilderService;
use Admin\Core\Fields\Mutations\FieldToArray;
use Admin\Core\Helpers\File;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Facades\Admin\Helpers\SEOService;

class SiteBuilder extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2020-04-12 13:17:22';

    /*
     * Template name
     */
    public function name()
    {
        return _('Obsahové bloky');
    }

    protected $inMenu = false;

    protected $withoutParent = true;

    /*
     * This model can be assigned to any other model
     */
    protected $globalRelation = true;

    protected $reversed = true;

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'type' => 'name:Vyberte typ bloku|column_name:Typ bloku|hidden|type:select|component:SiteBuilderBlockSelect|required',
        ];
    }

    protected $settings = [
        'increments' => false,
        'autoreset' => false,
        'grid.header' => false,
        'grid.default' => 'half',
        'table.switchcolumns' => false,
        'title.create' => 'Pridajte nový blok',
        'title.update' => 'Upravujete blok',
        'title.rows' => 'Zoznam blokov',
        'buttons.create' => 'Pridať nový blok',
        'buttons.insert' => 'Pridať blok',
        'pagination.limit' => 20,
        'columns._block_name.name' => 'Typ bloku',
        'columns._block_value.name' => 'Hodnota',
        'columns._block_value.encode' => false,
    ];

    public function options()
    {
        return [
            'type' => $this->getBlockTypes()
        ];
    }

    public function getBlockTypes()
    {
        $types = [];

        foreach(SiteBuilderService::getClasses() as $class) {
            $types[$class->getPrefix()] = [
                'name' => $class->getName(),
                'icon' => $class->getIcon(),
            ];
        }

        return $types;
    }

    public function mutateFields($fields)
    {
        $types = [];

        foreach(SiteBuilderService::getClasses() as $class) {
            $types[] = Group::fields($class->getMutatedFields())
                             ->prefix($class->getPrefix())
                             ->add('hidden')
                             ->id('sb_block_'.$class->getPrefix());
        }

        $fields->push(
            Group::fields($types)->id('sitebuilder_blocks')
        );
    }

    public function setAdminAttributes($attributes)
    {
        if ($block = $this->getBlockType()){
            $attributes['_block_name'] = $block->getName();
            $attributes['_block_value'] = $this->getBlockShortValue($block);
        }

        return $attributes;
    }

    private function getBlockShortValue($block)
    {
        foreach ($block->getFields() as $key => $field) {
            $fieldKey = $block->getPrefix().'_'.$key;

            if ( ! ($value = $this->{$fieldKey}) ){
                continue;
            }

            $field = (new FieldToArray)->update($field);
            $fieldType = @$field['type'] ?: 'string';

            if ( in_array($fieldType, ['string', 'text', 'longtext', 'editor', 'longeditor', 'integer', 'decimal', 'select']) ) {
                return $this->makeDescription($fieldKey, 100);
            } elseif ( in_array($fieldType, ['file']) && @$field['image'] === true ) {
                $images = [];

                foreach (array_wrap($value) as $image) {
                    if ( $image instanceof File ) {
                        $images[] = '<img src="'.$image->resize(40, 40)->url.'">';
                    }
                }

                return implode(' ', $images);
            }
        }
    }

    public function getBlockType()
    {
        return SiteBuilderService::getByType($this->type);
    }
}