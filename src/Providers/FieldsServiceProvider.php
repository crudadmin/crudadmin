<?php

namespace Admin\Providers;

use Fields;
use Admin\Fields\Mutations;
use Admin\Contracts\Migrations\Types;
use Admin\Contracts\Migrations\Columns;
use Illuminate\Support\ServiceProvider;

class FieldsServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function boot()
    {
        //Register global crudadmin fields attributes
        Fields::addAttribute([
            'title', 'placeholder', 'hidden', 'disabled', 'orderBy', 'limit', 'multirows',
            'invisible', 'component', 'column_name', 'removeFromForm', 'hideFromForm', 'phone_link',
            'ifDoesntExists', 'hideOnUpdate', 'ifExists', 'hideOnCreate', 'removeFromFormIf', 'removeFromFormIfNot'
        ]);

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
        ]);

        //We need register fields mutators into crudadmin core
        Fields::addMutation([
            Mutations\InterfaceRules::class,
            Mutations\AddSelectSupport::class,
            Mutations\AddLocalizationSupport::class,
            Mutations\UpdateDateFormat::class,
            Mutations\AddEmptyValue::class,
        ]);
    }
}
