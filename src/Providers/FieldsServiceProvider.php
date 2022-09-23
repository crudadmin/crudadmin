<?php

namespace Admin\Providers;

use Fields;
use Admin\Fields\Mutations;
use Admin\Contracts\Migrations\Types;
use Admin\Contracts\Migrations\Columns;
use Illuminate\Support\ServiceProvider;

class FieldsServiceProvider extends ServiceProvider
{
    protected $allFields = [
        'title', 'placeholder', 'hidden', 'orderBy', 'limit', 'multirows', 'defaultByOption', 'tooltip', 'editor_height',
        'column_visible', 'component', 'sub_component', 'column_component', 'component_data', 'table_request_present',
        'column_name', 'phone_link', 'ifExists', 'ifDoesntExists', 'hideOnUpdate', 'hideOnCreate', 'keepInRequest', 'encrypted',
        'inaccessible' => true, 'inaccessible_column' => true, 'invisible' => true, 'disabled' => true, 'readonly' => true,
        'removeFromForm' => true, 'hideFromForm' => true,
        'removeField' => true, 'hideField' => true, 'visibleField' => true,
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function boot()
    {
        //Register global crudadmin fields attributes
        $this->registerAllFields();

        //Add CrudAdmin additional column type
        Fields::addColumnTypeBefore([
            Types\ImaginaryType::class,
        ]);

        Fields::addColumnType([
            Types\StringTypes::class,
            Types\EditorType::class,
        ]);

        //Add CrudAdmin static columns
        Fields::addStaticColumn([
            Columns\LanguageId::class,
            Columns\Sortable::class,
            Columns\EncryptedSearch::class,
        ]);

        //We need register fields mutators into crudadmin core
        Fields::addMutation([
            Mutations\InterfaceRules::class,
            Mutations\PermissionsSupport::class,
            Mutations\AddSelectSupport::class,
            Mutations\AddLocalizationSupport::class,
            Mutations\UpdateDateFormat::class,
            Mutations\AddEmptyValue::class,
        ]);
    }

    /**
     * Register all fields with attribute types
     *
     * @return  void
     */
    public function registerAllFields()
    {
        foreach ($this->allFields as $key => $field) {
            if ( $field === true ) {
                Fields::addAttribute($key);

                foreach (['If', 'IfNot', 'IfIn', 'IfNotIn'] as $postfix) {
                    Fields::addAttribute($key.$postfix);
                }
            } else {
                Fields::addAttribute($field);
            }
        }
    }
}
