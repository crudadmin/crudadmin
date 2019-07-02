<?php

namespace Admin\Providers;

use Illuminate\Support\ServiceProvider;
use Fields;

class FieldsServiceProvider extends ServiceProvider {

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
            'ifDoesntExists', 'hideOnUpdate', 'ifExists', 'hideOnCreate'
        ]);


        //We need register fields mutators into crudadmin core
        Fields::addMutation([
            \Admin\Fields\Mutations\InterfaceRules::class,
            \Admin\Fields\Mutations\AddSelectSupport::class,
            \Admin\Fields\Mutations\AddLocalizationSupport::class,
            \Admin\Fields\Mutations\UpdateDateFormat::class,
            \Admin\Fields\Mutations\AddEmptyValue::class,
        ]);
    }
}