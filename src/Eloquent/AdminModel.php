<?php

namespace Admin\Eloquent;

use Admin;
use Localization;
use Admin\Eloquent\Concerns\ModelIcons;
use Admin\Eloquent\Concerns\ModelRules;
use Admin\Eloquent\Concerns\Uploadable;
use Admin\Eloquent\Concerns\Historiable;
use Admin\Eloquent\Concerns\VueComponent;
use Illuminate\Database\Eloquent\Builder;
use Admin\Eloquent\Concerns\HasAttributes;
use Admin\Eloquent\Concerns\AdminModelTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Admin\Eloquent\Concerns\ModelLayoutBuilder;
use Admin\Core\Eloquent\AdminModel as CoreAdminModel;

class AdminModel extends CoreAdminModel
{
    use AdminModelTrait,
        ModelLayoutBuilder,
        HasAttributes,
        ModelRules,
        VueComponent,
        Historiable,
        Uploadable,
        ModelIcons,
        SoftDeletes;

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
     * History feature for model
     */
    protected $history = false;

    /*
     * Delete old rewrited files from
     */
    protected $delete_files = true;

    /*
     * Show sub-childs in tabs
     */
    protected $inTab = true;

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
    public function canDelete($row)
    {
        return true;
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
        if (! Admin::isAdmin() && $this->publishable == true) {
            static::addGlobalScope('publishable', function (Builder $builder) {
                $builder->withPublished();
            });
        }

        //We need leave constructor at the bottom
        //because of booting cachable properties.
        //We want cache also booted properties from crudadmin,
        //and also a crudadmin framework
        parent::__construct($attributes);
    }
}
