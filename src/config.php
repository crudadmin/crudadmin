<?php
return [
    /*
     * Administration name
     */
    'name' => 'My Admin',

    /*
     * Administration groups names
     */
    'groups' => [
        'settings' => 'Nastavenia',
    ],

    /*
     * Add multi language mutations support
     */
    'localization' => false,

    /*
     * Removes default language segment from url
     */
    'localization_remove_default' => false,

    /*
     * Gettext support
     */
    'gettext' => false,

    /*
     * Directories for loading gettext translations
     */
    'gettext_source_paths' => [
        'resources/views',
        'app/Http',
        'app/Http/Controllers',
        'app/Http/Middleware',
    ],

    /*
     * Addition gettext supported codes which are not includes native in administration ( sk_SK, cs_CZ ... )
     */
    'gettext_supported_codes' => [ ],

    /*
     * Custom rules aliases
     */
    'custom_rules' => [
        'image' => 'type:file|image|max:5120',
        'belongsTo' => 'type:select',
        'belongsToMany' => 'type:select|array',
        'multiple' => 'array',
        'multirows' => 'array',
    ],

    /*
     * Global rules on fields type
     */
    'global_rules' => [
        'string' => 'max:255',
        'integer' => 'integer|max:4294967295',
        'decimal' => 'numeric',
        'file' => 'max:10240|file|nullable',
        'editor' => 'hidden',
        'password' => 'hidden',
        'checkbox' => 'boolean',
        'date' => 'date_format:d.m.Y',
        'datetime' => 'date_format:d.m.Y H:i',
        'time' => 'date_format:H:i',
    ],
];