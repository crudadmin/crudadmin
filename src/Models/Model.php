<?php

namespace Gogol\Admin\Models;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Gogol\Admin\Traits\AdminModelTrait;
use Gogol\Admin\Traits\Sluggable;
use Gogol\Admin\Traits\HasChildrens;
use Gogol\Admin\Traits\ModelRelationships;
use Gogol\Admin\Traits\Uploadable;
use Gogol\Admin\Traits\Validation;
use Localization;
use Admin;

class Model extends BaseModel
{
    use SoftDeletes, ModelRelationships, Sluggable, Uploadable, Validation, HasChildrens, AdminModelTrait;

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
    protected $skipDroppingColumn = false;

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

    public function scopeAdminRows($query)
    {

    }

    public function __construct(array $attributes = [])
    {
        //Boot base model trait
        $this->initTrait();

        /**
         * Add global scope for ordering
         */
        if ( $this->isSortable() )
        {
            static::addGlobalScope('order', function(Builder $builder) {
                $builder->orderBy('_order', 'DESC');
            });
        } else if ( Admin::isAdmin() ){
            static::addGlobalScope('order', function(Builder $builder) {
                $builder->orderBy('id', 'DESC');
            });
        }

        /**
         * Add global scope for publishing extepts admin interface
         */
        if ( ! Admin::isAdmin() && $this->publishable == true )
        {
            static::addGlobalScope('publishable', function(Builder $builder) {
                $builder->where('published_at', '!=', null);
            });
        }

        parent::__construct($attributes);
    }

}