<?php

namespace Gogol\Admin\Models;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Gogol\Admin\Traits\AdminModelTrait;
use Gogol\Admin\Traits\Sluggable;
use Gogol\Admin\Traits\HasChildrens;
use Gogol\Admin\Traits\FieldProperties;
use Gogol\Admin\Traits\ModelRelationships;
use Gogol\Admin\Traits\ModelIcons;
use Gogol\Admin\Traits\Uploadable;
use Gogol\Admin\Traits\Validation;
use Localization;
use Admin;

class Model extends BaseModel
{
    use SoftDeletes,
        ModelRelationships,
        Sluggable,
        Uploadable,
        Validation,
        HasChildrens,
        AdminModelTrait,
        ModelIcons,
        FieldProperties;

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
     * Model icon
     */
    protected $icon = null;

    /*
     * Automatic form and database generation
     */
    protected $fields = [];

    /*
     * Filter rows by selected language
     */
    public function scopeLocalization($query, $language_id = null)
    {
        if ( ! $this->isEnabledLanguageForeign() )
            return $query;

        if ( ! is_numeric( $language_id ) )
        {
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
        static::addGlobalScope('order', function(Builder $builder) {
            $builder->addSorting();
        });

        /**
         * Add global scope for publishing extepts admin interface
         */
        if ( ! Admin::isAdmin() && $this->publishable == true )
        {
            static::addGlobalScope('publishable', function(Builder $builder) {
                $builder->withPublished();
            });
        }

        parent::__construct($attributes);
    }

}