<?php

namespace Admin\Eloquent;

use Admin;
use Admin\Core\Eloquent\AdminModel as CoreAdminModel;
use Admin\Eloquent\Concerns\AdminModelTrait;
use Admin\Eloquent\Concerns\HasAttributes;
use Admin\Eloquent\Concerns\HasButtons;
use Admin\Eloquent\Concerns\HasEloquentReplicator;
use Admin\Eloquent\Concerns\HasEncryption;
use Admin\Eloquent\Concerns\HasExporter;
use Admin\Eloquent\Concerns\HasPermissions;
use Admin\Eloquent\Concerns\HasSiteBuilder;
use Admin\Eloquent\Concerns\HasSiteTree;
use Admin\Eloquent\Concerns\Historiable;
use Admin\Eloquent\Concerns\ModelIcons;
use Admin\Eloquent\Concerns\ModelLayoutBuilder;
use Admin\Eloquent\Concerns\ModelRules;
use Admin\Eloquent\Concerns\VueComponent;
use Admin\Eloquent\Modules\AdminCustomizationModule;
use Admin\Eloquent\Modules\FilterStateModule;
use Admin\Eloquent\Modules\GlobalRelationModule;
use Admin\Eloquent\Modules\HistoryModule;
use Admin\Eloquent\Modules\SeoModule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Localization;

class AdminModel extends CoreAdminModel
{
    use AdminModelTrait,
        ModelLayoutBuilder,
        HasEloquentReplicator,
        HasExporter,
        HasAttributes,
        HasPermissions,
        ModelRules,
        VueComponent,
        Historiable,
        SoftDeletes,
        HasSiteBuilder,
        HasSiteTree,
        HasButtons,
        HasEncryption;

    /*
     * Template name
     */
    protected $name = '';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    /*
     * Group
     */
    protected $group = null;

    /*
     * Enable multilanguages
     */
    protected $localization = false;

    /*
     * Model Parent
     * Eg. Articles::class,
     */
    protected $belongsToModel = null;

    /*
     * Enable adding new rows
     */
    protected $insertable = true;

    /*
     * Enable updating rows
     */
    protected $editable = true;

    /*
     * Enable deleting rows
     */
    protected $deletable = true;

    /*
     * Enable publishing rows
     */
    protected $publishable = true;

    /*
     * Enable sorting rows
     */
    protected $sortable = true;

    /*
     * Row can be displayed in preview mode, also when editable is disabled.
     */
    protected $displayable = false;

    /*
     * Ordering rows
     */
    protected $orderBy = ['_order', 'DESC'];

    /*
     * Reverse acutal order
     */
    protected $reversed = false;

    /*
     * Minimum page rows
     * Default = 0
     */
    protected $minimum = 0;

    /*
     * Maximum page rows
     * Default = 0 = ∞
     */
    protected $maximum = 0;

    /*
     * Automatic sluggable
     */
    protected $sluggable = null;

    /*
     * Acivate/deactivate model in administration
     */
    protected $active = true;

    /*
     * Skipping dropping columns into database in migration
     */
    protected $skipDropping = [];

    /*
     * Single row in table, automatically set minimum and maximum to 1
     */
    protected $single = false;

    /*
     * Is single relation model merged with parent form
     */
    protected $inParent = false;

    /*
     * History feature for model
     */
    protected $history = false;

    /*
     * Delete old rewrited files from
     */
    protected $delete_files = true;

    /**
     * Show model in menu even if is relation
     *
     * @var  null/true/false
     */
    protected $inMenu = null;

    /*
     * If child model can be added without parent model
     */
    protected $withoutParent = false;

    /*
     * Allow nullable relation for belongsToModel
     */
    protected $nullableRelation = false;

    /*
     * Which id's will be reserved and can not be removed
     */
    protected $reserved = [];

    /*
     * Model icon
     */
    protected $icon = null;

    /*
     * Has available seo module
     */
    protected $seo = false;

    /*
     * Visible seo attributes in toArray()
     */
    protected $seoVisible = false;

    /*
     * This model can be assigned to any other model without specific relation key
     */
    protected $globalRelation = false;

    /*
     * Compress all uploaded images with lossy compression
     */
    protected $imageLossyCompression = true;

    /*
     * Compress all uploaded images with lossy compression.
     * Is automatically disabled when lossyCompression is off
     */
    protected $imageMaximumProportions = true;

    /*
     * Compress all uploaded images with lossless compression
     */
    protected $imageLosslessCompression = true;

    /*
     * Publishable feature is turned for each model.
     * Via this property we can manage global publishable for all models
     */
    static $globalPublishable = true;
    static $globalModelPublishable = [];

    /*
     * Admin modules
     */
    protected $modules = [
        SeoModule::class,
        AdminCustomizationModule::class,
        GlobalRelationModule::class,
        FilterStateModule::class,
        HistoryModule::class,
    ];

    /**
     * We can turn off publishable globally for all models
     *
     * @param  bool  $state
     */
    public static function setGlobalPublishable($state)
    {
        self::$globalPublishable = $state;
    }

    /**
     * We can turn off publishable globally for all models
     *
     * @param  bool  $state
     */
    public static function setGlobalModelPublishable($state)
    {
        $table = (new static)->getTable();

        if ( $state === true ) {
            self::$globalModelPublishable[$table] = true;
        } else {
            self::$globalModelPublishable[$table] = false;
        }
    }

    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootSoftDeletes()
    {
        $model = (new static);

        //Enable soft deletes when timestamps are turned on or column deleted_at is available
        if ( $model->hasSoftDeletes() ) {
            static::addGlobalScope(new SoftDeletingScope);
        }
    }

    /**
     * You can modify model default permissions in this method
     *
     * @param  array  $permissions
     */
    public function setModelPermissions($permissions)
    {
        return $permissions;
    }

    /*
     * Filter rows by selected language
     */
    public function scopeLocalization($query, $language_id = null)
    {
        if (! $this->isEnabledLanguageForeign()) {
            return $query;
        }

        if (! is_numeric($language_id)) {
            $language_id = Localization::get()->getKey();
        }

        $query->where('language_id', $language_id);
    }

    /*
     * Returns also unpublished rows
     */
    public function scopeWithUnpublished($query)
    {
        $query->withoutGlobalScope('publishable');
    }

    /*
     * Query for rows displayed in administration
     */
    public function scopeAdminRows($query)
    {
    }

    /*
     * Check if user can delete row
     */
    public function canDelete()
    {
        return true;
    }

    /**
     * Check if publishable scope is enabled by default
     *
     * @return  bool
     */
    private function isPublishableAllowed()
    {
        if ( $this->publishable == false ){
            return false;
        }

        if ( Admin::isAdmin() === true ) {
            return false;
        }

        if ( self::$globalPublishable === false ) {
            return false;
        }

        return (self::$globalModelPublishable[$this->getTable()] ?? true) === true;
    }

    public function __construct(array $attributes = [])
    {
        //Boot base model trait
        $this->initTrait();

        //Add sortable functions
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->addSorting();
        });

        /*
         * Add global scope for publishing extepts admin interface
         */
        if ( $this->isPublishableAllowed() ) {
            static::addGlobalScope('publishable', function (Builder $builder) {
                //We want check publishable again, because state may change
                if ( $this->isPublishableAllowed() ) {
                    $builder->withPublished();
                }
            });
        }

        //We need leave constructor at the bottom
        //because of booting cachable properties.
        //We want cache also booted properties from crudadmin,
        //and also a crudadmin framework
        parent::__construct($attributes);
    }


    /**
     * Get the fully qualified "deleted at" column.
     *
     * @return string
     */
    public function getQualifiedDeletedAtColumn()
    {
        //Fix for non-deletable columns
        if ( !$this->hasSoftDeletes() ){
            return;
        }

        return $this->qualifyColumn($this->getDeletedAtColumn());
    }
}
