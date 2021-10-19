<?php

return [
    /*
     * Application namespace
     */
    'app_namespace' => 'App',

    /*
     * Default crudadmin storage
     */
    'disk' => 'crudadmin.uploads',

    /*
     * From which directories CrudAdmin should load Admin Modules
     */
    'models' => [
        'App' => app_path(),
        'App\Model' => app_path('Model'),
        'App\Models' => app_path('Models'),
        'App\Eloquent' => app_path('Eloquent'),
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
        'app/Mail',
        'app/Notifications',
        'app/Model',
        'app/Models',
        'app/Eloquent',
        'resources/views',
        'resources/assets/js',
        'resources/js',
        public_path('vendor/crudadmin/js/FrontendEditor.js'),
    ],

    /*
     * Directories for loading gettext translations
     */
    'gettext_admin_source_paths' => [
        'app/Admin',
        'app/Model',
        'app/Models',
        __DIR__.'/../',
        storage_path('crudadmin/lang/cache'),
        base_path('vendor/crudadmin/resources/src/Resources/js'),
        base_path('vendor/crudadmin/resources/src/Resources/lang/sk'),
        base_path('vendor/crudadmin/resources/src/Resources/views'),
        base_path('vendor/crudadmin/resources/src/Controllers'),
    ],

    /*
     * Addition gettext supported codes which are not includes native in administration ( sk_SK, cs_CZ ... )
     */
    'gettext_supported_codes' => [],

    /*
     * Permanently delete files after deleted row in db or after overridden uploaded files
     */
    'reduce_space' => true,

    /*
     * Automaticaly resize in aspect ratio all uploaded images which exceed given resolutions
     */
    'image_auto_resize' => true,
    'image_max_width' => 1920,
    'image_max_height' => 1200,

    /*
     * Image lossy compression in %.
     * Eg. 90|true|false
     */
    'image_lossy_compression_quality' => 80,

    /*
     * Image lossless compression
     * true/false
     */
    'image_lossless_compression' => true,

    /*
     * Automatically create webp image for all resized resource
     * true/false
     */
    'image_webp' => false,

    /*
     * Allow slug history table for 301 redirect from old slugs to new slugs
     */
    'sluggable_history' => false,

    /*
     * Allow admin model changes history
     */
    'history' => false,

    /*
     * Allow seo module
     */
    'seo' => false,

    /**
     * Allow ckfinder in ckeditor, or use filemanager
     */
    'ckfinder' => false,

    /*
     * Filemanager
     */
    'filemanager' => true,

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
        'routes/web.php',
    ],

    /*
     * Components directories
     */
    'components' => [
        'resources/views/admin/components',
        __DIR__.'/../Resources/components',
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
        'inaccessible' => 'invisible|inaccessible',
        'invisible' => 'hidden|removeFromForm|invisible',
        'unsigned' => 'min:0',
    ],

    /*
     * Global rules on fields type
     */
    'global_rules' => [
        'string' => 'max:255',
        'color' => 'max:7',
        'integer' => 'integer|max:4294967295',
        'decimal' => 'numeric',
        'file' => 'max:10240|file|nullable',
        'editor' => 'hidden',
        'longeditor' => 'hidden',
        'password' => 'hidden',
        'checkbox' => 'boolean',
        'date' => 'date_format_multiple:d.m.Y,Y-m-d,Y-m-d\TH:i:s.u\Z,Y-m-d\TH:i:sP|nullable',
        'datetime' => 'date_format_multiple:d.m.Y H:i,Y-m-d H:i,Y-m-d H:i:s,Y-m-d\TH:i:s.u\Z,Y-m-d\TH:i:sP|nullable',
        'time' => 'date_format_multiple:H:i|nullable',
        'json' => 'hidden',
    ],

    /*
     * Resources/ui service provider
     */
    'resources_provider' => Admin\Resources\Providers\AppServiceProvider::class,

    /*
     * Allow admin roles
     */
    'admin_roles' => false,

    /*
     * If uploaded images will dissapear, they will be replaced with stock image
     */
    'rewrite_missing_upload_images' => env('ADMIN_REWRITE_MISSING_IMAGES', true),

    /*
     * Fow uploads which are not image type
     */
    'uploadable_allowed_extensions' => '7z,aiff,asf,avi,bmp,csv,svg,doc,docx,fla,flv,gif,gz,gzip,jpeg,jpg,mid,mov,mp3,mp4,m4a,mpc,mpeg,mpg,ods,odt,pdf,png,ppt,pptx,pxd,qt,ram,rar,rm,rmi,rmvb,rtf,sdc,sitd,swf,sxc,sxw,tar,tgz,tif,tiff,txt,vsd,wav,wma,wmv,xls,xlsx,zip,xml',

    /*
     * All available sitebuilder types
     */
    'sitebuilder_types' => [
        Admin\Contracts\Sitebuilder\Types\Editor::class,
        Admin\Contracts\Sitebuilder\Types\Text::class,
        Admin\Contracts\Sitebuilder\Types\Image::class,
        Admin\Contracts\Sitebuilder\Types\Iframe::class,
    ],
];
