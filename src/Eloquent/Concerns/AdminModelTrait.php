<?php

namespace Admin\Eloquent\Concerns;

use Admin;
use Admin\Eloquent\Authenticatable;
use Admin\Helpers\AdminCollection;
use Carbon\Carbon;
use Fields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait AdminModelTrait
{
    /*
     * Update model data before saving
     *
     * @see Illuminate\Database\Eloquent\Model
     */
    public function save(array $options = [])
    {
        //If model has needs sluggable field
        if ($this->sluggable != null) {
            $this->sluggable();
        }

        //If is creating new row
        if ($this->exists == false) {
            //Add auto order incement into row, when row is not in database yet
            if ($this->isSortable() && ! array_key_exists('_order', $this->attributes)) {
                $this->attributes['_order'] = $this->withTrashed()->count();
            }

            //Add auto publishing rows
            if ($this->publishable == true && ! array_key_exists('published_at', $this->attributes)) {
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
        if (! Admin::isAdmin()) {
            $this->checkForModelRules(['created', 'updated'], true);
        }

        return $instance;
    }

    //Add fillable and dates fields
    public function initTrait()
    {
        //Make single row model if is needed
        $this->makeSingle();

        //Checks if is model in sortable mode
        $this->setOrder();

        //Remove visible fields in model
        $this->removeVisible();

        //Remove appends
        $this->removeAppends();

        //If admin models has been loaded
        //because we do need loaded all models to perform
        //this features...
        if (Admin::isLoaded() === true) {
            $bootAdminModel = function(){
                //Remove hidden when is required in admin
                $this->removeHidden();
            };

            //If is newest version of crudadmin framework, then cache properties.
            //For older versions of crudadmin framework just use real time generating of properties.
            //This switch is used for backwand compactibility for older versions of crudadmin framework
            if ( method_exists($this, 'cachableFieldsProperties') ) {
                $this->cachableFieldsProperties($bootAdminModel);
            } else {
                $bootAdminModel();
            }
        }
    }

    /*
     * Set selectbox field to automatic json format
     */
    protected function makeCastable()
    {
        parent::makeCastable();

        //Add cast for order field
        if ($this->isSortable()) {
            $this->casts['_order'] = 'integer';
        }

        //Add cast into localized slug column
        if ( $this->hasLocalizedSlug() ){
            $this->casts['slug'] = 'json';
        }
    }

    /**
     * Set fillable property for laravel model from admin fields.
     */
    protected function makeFillable()
    {
        parent::makeFillable();

        //Allow language foreign
        if ($this->isEnabledLanguageForeign()) {
            $this->fillable[] = 'language_id';
        }
    }

    /*
     * Turn model to single row in database
     */
    protected function makeSingle()
    {
        if ($this->single === true) {
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

        if ($this instanceof Authenticatable) {
            $this->hidden = [];

            foreach ($this->getFields() as $key => $field) {
                //Hide all password fields
                if ( $this->isFieldType($key, 'password') ) {
                    $this->hidden[] = $key;
                }
            }

            return;
        }

        $columns = array_merge(array_keys($this->getFields()), ['id', 'created_at', 'updated_at', 'published_at', 'deleted_at', '_order', 'slug', 'language_id']);

        foreach ($columns as $column) {
            if (in_array($column, $this->hidden)) {
                unset($this->hidden[array_search($column, $this->hidden)]);
            }
        }

        //Removes foreign column from hidden
        if (count($this->hidden) > 0 && is_array($columns = $this->getForeignColumn())) {
            foreach ($columns as $column) {
                if (in_array($column, $this->hidden)) {
                    unset($this->hidden[array_search($column, $this->hidden)]);
                }
            }
        }
    }

    /*
     * Remove uneccessary visible properties from model in administration
     */
    protected function removeVisible()
    {
        if ( Admin::isAdmin() == false )
            return;

        $this->visible = [];
    }

    /*
     * Remove uneccessary appends properties from model in administration
     */
    protected function removeAppends()
    {
        if ( Admin::isAdmin() == false )
            return;

        $this->appends = [];
    }

    /*
     * Set property of sorting rows to right mode
     */
    protected function setOrder()
    {
        //If is turned of sorting of rows
        if (! $this->isSortable() && $this->orderBy[0] == '_order') {
            $this->orderBy[0] = 'id';
        }

        if (! array_key_exists(1, $this->orderBy)) {
            $this->orderBy[1] = 'ASC';
        }

        /*
         * Reverse default order
         */
        if ($this->reversed === true) {
            $this->orderBy[1] = strtolower($this->orderBy[1]) == 'asc' ? 'DESC' : 'ASC';
        }
    }

    /*
     * Returns short values of fields for content table of rows in administration
     */
    public function getBaseFields()
    {
        $fields = $this->getColumnNames();

        //Remove hidden fields
        foreach ($this->getFields() as $key => $field) {
            //Skip hidden fields and fields with long values
            if ($this->hasFieldParam($key, 'hidden', true) && in_array($key, $fields) ) {
                unset($fields[array_search($key, $fields)]);
            }
        }

        //Remove delete_at column from list
        if (in_array('deleted_at', $fields)) {
            unset($fields[array_search('deleted_at', $fields)]);
        }

        //Push also additional needed columns from request
        if (request()->has('enabled_columns')) {
            $fields = array_merge($fields, array_diff(explode(';', request('enabled_columns', '')), $fields));
        }

        return array_values($fields);
    }

    public function scopeFilterByParentOrLanguage($query, $subid, $langid, $parent_table = null)
    {
        if ($langid > 0) {
            $query->localization($langid);
        }

        if ($subid > 0) {
            $column = $this->getForeignColumn($parent_table);

            if ($parent_table === null && count($column) == 1) {
                $column = array_values($column)[0];
            }

            //Find by relationship
            if ($column) {
                $query->where($column, $subid);
            }

            //Find by global relationship
            else if ( $this->getProperty('globalRelation') === true ) {
                $query->where('_table', $parent_table)
                      ->where('_row_id', $subid);
            }
        }

        //If is not parent table, but rows can be related into recursive relation
        if (! $parent_table && (int) $subid == 0) {
            if (in_array(class_basename(get_class($this)), $this->getBelongsToRelation(true))) {
                $query->whereNull($this->getForeignColumn($this->getTable()));
            }
        }

        //Use custom scopes for admin rows
        if ( is_array($scopes = request('scopes')) && count($scopes) ) {
            foreach ($scopes as $scope => $attributes) {
                if ( method_exists($this, 'scope'.$scope) ){
                    $params = explode(';', $attributes);

                    $query->{$scope}(...$params);
                }
            }
        }
    }

    public function scopeFilterByParentField($query, $parentTable, $fieldKey, $id)
    {
        $model = Admin::getModelByTable($parentTable);
        $field = $model->getField($fieldKey);
        $relationType = isset($field['belongsToMany']) ? 'belongsToMany' : 'belongsTo';

        $relationProperties = $model->getRelationProperty($fieldKey, $relationType);

        $query->whereExists(function($query) use ($relationProperties, $id) {
            $query->select(DB::raw(1))
                  ->from($relationProperties[3])
                  ->whereRaw($relationProperties[3].'.'.$relationProperties[7].'='.$relationProperties[0].'.id')
                  ->where($relationProperties[6], $id);
        });
    }

    /**
     * Filter admin rows by parent group settings
     */
    public function scopeFilterByParentGroup($query)
    {
        //If is no admin parent model
        if ( !$parent = $this->getParentRow() )
            return;

        $groups = $parent->getFieldsGroups();

        foreach ($this->flattenGroups($groups) as $group) {
            if ( $group instanceof Admin\Fields\Group && $group->getModel() === $this->getTable() && $closure = $group->getWhere() ) {
                return $closure($query, $this->getParentRow());
            }
        }
    }

    /**
     * Flatten fields model groups
     *
     * @param  mixed  $groups
     * @param  array|null  $array
     * @return array
     */
    public function flattenGroups($groups, $array = [])
    {
        if ( !is_array($groups) ) {
            return $array;
        }

        foreach ($groups as $group) {
            if ( Fields::isFieldGroup($group) ) {
                $array = array_merge($array, [$group], $this->flattenGroups($group->fields));
            }
        }

        return $array;
    }

    /*
     * Checks if is enabled language foreign column for actual model.
     */
    public function isEnabledLanguageForeign()
    {
        if (
            Admin::isEnabledLocalization() &&
            (
                $this->getTable() != 'languages' && $this->belongsToModel == null && $this->localization === true
                || $this->localization === true
            )
        ) {
            return true;
        }

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
        return new AdminCollection($models);
    }

    /*
     * Return short description of content for meta tags etc...
     */
    public function makeDescription($field, $limit = 150)
    {
        $string = $this->getValue($field);

        $string = html_entity_decode($string);
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
        $this->adminRows();

        return $this;
    }

    /*
     * Returns if has model sortabel support
     */
    public function isSortable($with_order = true)
    {
        if ($this->orderBy[0] != '_order') {
            return false;
        }

        if ($this->minimum == 1 && $this->maximum == 1) {
            return false;
        }

        return $this->getProperty('sortable');
    }

    /*
     * Enable sorting
     */
    public function scopeAddSorting($query)
    {
        $column = $this->orderBy[0];

        if (count(explode('.', $column)) == 1) {
            $column = $this->getTable().'.'.$this->orderBy[0];
        }

        /*
         * Add global scope for ordering
         */
        $query->orderBy($column, $this->orderBy[1]);
    }

    /*
     * Returns if form is in reversed mode, it mean that new rows will be added on end
     */
    public function isReversed()
    {
        if (! array_key_exists(2, $this->orderBy) || $this->orderBy[2] != true) {
            return false;
        }

        return in_array($this->orderBy[0], ['id', '_order']) && strtolower($this->orderBy[1]) == 'asc';
    }

    /*
     * Where are stored VueJS components
     */
    protected function getComponentPaths()
    {
        return resource_path('views/admin/components/fields');
    }

    /*
     * Return form keys prefix for given model
     */
    public function getModelFormPrefix($key = '')
    {
        if ( $this->getProperty('inParent') === false )
            return $key;

        return '$'.$this->getTable().'_'.$key;
    }

    /**
     * Clone the model into a new, non-existing instance.
     * We need replicate model without _order column.
     * If we would keep previous _order, new row will have same order...
     * And this will cause weird behaviour in administration...
     *
     * @param  array|null  $except
     * @return static
     */
    public function replicate(array $except = null)
    {
        if ( $this->isSortable() ) {
            return parent::replicate(array_merge($except ?: [], ['_order']));
        }

        return parent::replicate($except);
    }
}
