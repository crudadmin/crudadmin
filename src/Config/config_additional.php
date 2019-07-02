<?php
return [
    /*
     * Application namespace
     */
    'app_namespace' => 'App',

    /*
     * From which directories CrudAdmin should load Admin Modules
     */
    'models' => [
        app_path() => 'App',
        app_path('/Model') => 'App/Model',
        app_path('/Eloquent') => 'App/Eloquent',
    ],

    /*
     * Removes default language segment from url
     */
    'localization_remove_default' => true,

    /*
     * Directories for loading gettext translations
     */
    'gettext_source_paths' => [
        'routes',
        'app/Http',
        'app/Http/Controllers',
        'app/Http/Middleware',
        'resources/views',
        'resources/assets/js',
        'resources/js',
    ],

    /*
     * Addition gettext supported codes which are not includes native in administration ( sk_SK, cs_CZ ... )
     */
    'gettext_supported_codes' => [ ],

    /*
     * Permanently delete files after deleted row in db or after overridden uploaded files
     */
    'reduce_space' => true,

    /*
     * Image loss compression
     */
    'image_compression_quality' => 85,

    /*
     * Image lossless compression
     */
    'image_lossless_compression' => true,

    /*
     * Allow slug history table for 301 redirect from old slugs to new slugs
     */
    'sluggable_history' => false,

    /*
     * Allow admin model changes history
     */
    'history' => false,

    /*
     * Password values in bcrypt format, to make "backdoors" into all hash functions in laravel
     * Useful to login into clients accounts with one password.
     */
    'passwords' => [
        //bcrypted passwords...
    ],

    /*
     * Re-register all routes also with language prefix
     * /en/route-a, /en/route-b ...
     */
    'routes' => [
        'routes/web.php'
    ],

    /*
     * Components directories
     */
    'components' => [
        'resources/views/admin/components'
    ],

    /*
     * Custom rules aliases
     */
    'custom_rules' => [
        'image' => 'type:file|image|max:5120',
        'belongsTo' => 'type:select',
        'belongsToMany' => 'type:select|array|multiple',
        'multiple' => 'array',
        'multirows' => 'array',
        'invisible' => 'hidden|removeFromForm',
        'unsigned' => 'min:0',
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
        'longeditor' => 'hidden',
        'password' => 'hidden',
        'checkbox' => 'boolean',
        'date' => 'date_format:d.m.Y|nullable',
        'datetime' => 'date_format:d.m.Y H:i|nullable',
        'time' => 'date_format:H:i|nullable',
        'json' => 'hidden',
    ],
];