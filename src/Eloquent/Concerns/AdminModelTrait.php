<?php

namespace Admin\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Admin;

trait AdminModelTrait
{
    /*
     * Default fillable fields
     */
    private $_fillable = [ 'published_at' ];

    /*
     * Buffered fields in model
     */
    private $_fields = null;

    /*
     * Update model data before saving
     *
     * @see Illuminate\Database\Eloquent\Model
     */
    public function save(array $options = [])
    {
        //If model has needs sluggable field
        if ( $this->sluggable != null )
        {
            $this->sluggable();
        }

        //If is creating new row
        if ( $this->exists == false )
        {
            //Add auto order incement into row, when row is not in database yet
            if ( $this->isSortable() && ! array_key_exists('_order', $this->attributes) )
            {
                $this->attributes['_order'] = $this->withTrashed()->count();
            }

            //Add auto publishing rows
            if ( $this->publishable == true && ! array_key_exists('published_at', $this->attributes) )
            {
                $this->attributes['published_at'] = Carbon::now()->toDateTimeString();
            }
        }

        //Check for model rules
        $this->checkForModelRules(['creating', 'updating']);

        //Save model state before save action
        $this->backupExistsProperty();

        //Save model
        $instance = parent::save($options);

        //Check for model rules after row is already saved/created for frontend/console situations
        //for admin state events will be initialized in DataController after binding all relationships
        if ( ! Admin::isAdmin() )
            $this->checkForModelRules(['created', 'updated'], true);

        return $instance;
    }

    //Add fillable and dates fields
    public function initTrait()
    {
        //Make single row model if is needed
        $this->makeSingle();

        //Checks if is model in sortable mode
        $this->setOrder();

        if ( ! Admin::isLoaded() )
            return;

        //Remove hidden when is required in admin
        $this->removeHidden();
    }

    /*
     * Turn model to single row in database
     */
    protected function makeSingle()
    {
        if ( $this->single === true )
        {
            $this->minimum = 1;
            $this->maximum = 1;
        }
    }

    /*
     * Remove uneccessary properties from model in administration
     */
    protected function removeHidden()
    {
        if ( Admin::isAdmin() == false )
            return;

        if ( $this->getTable() == 'users' )
            return;

        $columns = array_merge(array_keys($this->getFields()), ['id', 'created_at', 'updated_at', 'published_at', 'deleted_at', '_order', 'slug', 'language_id']);

        foreach ($columns as $column) {
            if ( in_array($column, $this->hidden) )
            {
                unset($this->hidden[array_search($column, $this->hidden)]);
            }
        }

        //Removes foreign column from hidden
        if ( count($this->hidden) > 0 && is_array($columns = $this->getForeignColumn()))
        {
            foreach ($columns as $column) {
                if ( in_array($column, $this->hidden) )
                {
                    unset($this->hidden[array_search($column, $this->hidden)]);
                }
            }
        }
    }

    /*
     * Set property of sorting rows to right mode
     */
    protected function setOrder()
    {
        //If is turned of sorting of rows
        if ( ! $this->isSortable() && $this->orderBy[0] == '_order' )
        {
            $this->orderBy[0] = 'id';
        }

        if ( ! array_key_exists(1, $this->orderBy) )
        {
            $this->orderBy[1] = 'ASC';
        }

        /*
         * Reverse default order
         */
        if ( $this->reversed === true )
        {
            $this->orderBy[1] = strtolower($this->orderBy[1]) == 'asc' ? 'DESC' : 'ASC';
        }
    }

    /*
     * Returns short values of fields for content table of rows in administration
     */
    public function getBaseFields($all = false)
    {
        $fields = ['id'];

        //If has foreign key, add column name to base fields
        if ( $this->getForeignColumn() )
            $fields = array_merge($fields, array_values($this->getForeignColumn()));

        foreach ($this->getFields() as $key => $field)
        {
            //If is not requested all columns, then skip fields with long values
            if ( $all === false && ( array_key_exists('hidden', $field) && $field['hidden'] == true ) || array_key_exists('belongsToMany', $field))
                continue;

            $fields[] = $key;
        }

        //Insert skipped columns
        if ( is_array($this->skipDropping) )
        {
            foreach ($this->skipDropping as $key)
            {
                $fields[] = $key;
            }
        }

        //Add language id column
        if ($this->isEnabledLanguageForeign())
            $fields[] = 'language_id';

        if ( $this->sluggable != null )
        {
            $fields[] = 'slug';
        }

        if ( $this->isSortable() )
        {
            $fields[] = '_order';
        }

        if ( $this->publishable == true )
        {
            $fields[] = 'published_at';
        }

        $fields[] = 'updated_at';
        $fields[] = 'created_at';

        if ( $all === true )
        {
            $fields[] = 'deleted_at';
        }

        //Push also additional needed columns
        if ( request()->has('enabled_columns') )
            $fields = array_merge($fields, array_diff(explode(';', request('enabled_columns', '')), $fields));

        return $fields;
    }

    public function scopeFilterByParentOrLanguage($query, $subid, $langid, $parent_table = null)
    {
        if ( $langid > 0 )
            $query->localization($langid);

        if ( $subid > 0 ){
            $column = $this->getForeignColumn($parent_table);

            if ( $parent_table === null && count($column) == 1 )
                $column = array_values($column)[0];

            if ( $column )
                $query->where($column, $subid);
        }

        //If is not parent table, but rows can be related into recursive relation
        if ( ! $parent_table && (int)$subid == 0 ){
            if ( in_array(class_basename(get_class($this)), $this->getBelongsToRelation(true)) )
                $query->whereNull($this->getForeignColumn($this->getTable()));
        }

    }

    /*
     * Checks if is enabled language foreign column for actual model.
     */
    public function isEnabledLanguageForeign()
    {
        if ( ( $this->getTable()!='languages' && $this->belongsToModel == null && $this->localization === true || $this->localization === true ) && Admin::isEnabledMultiLanguages())
            return true;

        return false;
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new \Admin\Helpers\AdminCollection($models);
    }

    /*
     * Return short description of content for meta tags etc...
     */
    public function makeDescription($field, $limit = 150)
    {
        $string = $this->getValue($field);

        $string = strip_tags($string);
        $string = preg_replace("/(\n|\s|&nbsp;)+/u", ' ', $string);
        $string = trim($string, ' ');

        return str_limit($string, $limit);
    }

    /*
     * Add global scope for models in administration
     */
    public function getAdminRows()
    {
        $this->addGlobalScope('adminRows', function(Builder $builder){
            $builder->adminRows();
        });

        return $this;
    }

    /*
     * Returns if has model sortabel support
     */
    public function isSortable($with_order = true)
    {
        if ( $this->orderBy[0] != '_order' )
            return false;

        if ( $this->minimum == 1 && $this->maximum == 1 )
            return false;

        return $this->getProperty('sortable');
    }

    /*
     * Returns if form is in reversed mode, it mean that new rows will be added on end
     */
    public function isReversed()
    {
        if ( ! array_key_exists(2, $this->orderBy) || $this->orderBy[2] != true )
            return false;

        return in_array($this->orderBy[0], ['id', '_order']) && strtolower($this->orderBy[1]) == 'asc';
    }

    /*
     * Where are stored VueJS components
     */
    protected function getComponentPaths()
    {
        return resource_path('views/admin/components/fields');
    }
}