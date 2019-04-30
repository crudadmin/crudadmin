<?php

namespace Gogol\Admin\Traits;

use Illuminate\Database\Eloquent\Builder;
use Gogol\Admin\Models\ModelsHistory;
use Gogol\Admin\Helpers\File;
use Carbon\Carbon;
use Localization;
use Fields;
use Admin;
use Schema;
use DB;

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
     * On calling method
     *
     * @see Illuminate\Database\Eloquent\Model
     */
    public function __call($method, $parameters)
    {
        //Check if called method is not property, method of actual model or new query model
        if (!method_exists($this, $method) && !$parameters && !method_exists(parent::newQuery(), $method))
        {
            //Checks for db relationship of childrens into actual model
            if ( ($relation = $this->checkForChildrenModels($method)) || ($relation = $this->returnAdminRelationship($method)) )
            {
                return $this->checkIfIsRelationNull($relation);
            }
        }

        return parent::__call($method, $parameters);
    }

    /*
     * On calling property
     *
     * @see Illuminate\Database\Eloquent\Model
     */
    public function __get($key)
    {
        return $this->getValue($key, false);
    }

    /*
     * Returns modified called property
     */
    public function getValue($key, $force = true)
    {
        $force_check_relation = false;

        // //If is called field type file, then return file wrapper
        if ( ($field = $this->getField($key)) || ($field = $this->getField($key . '_id')) )
        {
            //Register file type response
            if ( $field['type'] == 'file' && !$this->hasGetMutator($key))
            {
                if ( $file = parent::__get($key) )
                {
                    //If is multilanguage file/s
                    if ( $this->hasFieldParam($key, ['locale'], true) ){
                        $file = $this->returnLocaleValue($file);
                    }

                    if ( is_array($file) || $this->hasFieldParam($key, ['multiple'], true) )
                    {
                        $files = [];

                        if ( !is_array($file) )
                            $file = [ $file ];

                        foreach ($file as $value)
                        {
                            if ( is_string($value) )
                                $files[] = File::adminModelFile($this->getTable(), $key, $value);
                        }

                        return $files;
                    } else {
                        return File::adminModelFile($this->getTable(), $key, $file);
                    }
                }

                return null;
            }

            //If field has not relationship, then return field value... This condition is here for better framework performance
            else if ( !array_key_exists('belongsTo', $field) && !array_key_exists('belongsToMany', $field) || substr($key, -3) == '_id' ){

                if ( array_key_exists('locale', $field) && $field['locale'] === true ) {
                    $object = parent::__get($key);

                    return $this->returnLocaleValue($object);
                }

                return parent::__get($key);
            } else {
                $force_check_relation = true;
            }
        }

        // //Register this offen called properties for better performance
        else if ( in_array($key, ['id', 'slug', 'created_at', 'published_at', 'deleted_at', 'pivot']) ) {
            if ( $key != 'slug' || $this->sluggable == true && $key == 'slug' )
                return parent::__get($key);
        }

        //If is called property with localization attribute, then add into called property language prefix
        else if (
            Localization::isEnabled()
            && ($localization = Localization::get())
            && ($slug = $localization->slug) && $field = $this->getField($key.'_'.$slug)
        ) {
            $key = $key.'_'.$slug;
        }

        //If is fields called from outside of class, then try to search relationship
        if ( in_array($key, ['fields']) || $force == true )
        {
            $force_check_relation = true;
        }

        // //Checks for relationship
        if ($force_check_relation === true || !property_exists($this, $key) && !method_exists($this, $key) && !array_key_exists($key, $this->attributes) && !$this->hasGetMutator($key) )
        {
            //If relations has been in buffer, but returns nullable value
            if ( $relation = $this->returnAdminRelationship($key, true) )
            {
                return $this->checkIfIsRelationNull($relation);
            }

            //Checks for db relationship childrens into actual model
            else if ( $relation = $this->checkForChildrenModels($key, true) ) {
                return $this->checkIfIsRelationNull($relation);
            }
        }

        return parent::__get($key);
    }

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

        //Add fillable fields
        $this->makeFillable();

        //Add dates fields
        $this->makeDateable();

        //Add cast attributes
        $this->makeCastable();

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

    /**
     * Set fillable property for laravel model from admin fields
     */
    protected function makeFillable()
    {
        foreach ($this->getFields() as $key => $field)
        {
            //Skip column
            if ( array_key_exists('belongsToMany', $field) )
                continue;

            $this->fillable[] = $key;
        }

        //Add published_at property
        foreach ($this->_fillable as $attribute)
        {
            $this->fillable[] = $attribute;
        }

        //If has relationship, then allow foreign key
        if ( $this->belongsToModel != null )
        {
            $this->fillable = array_merge(array_values($this->getForeignColumn()), $this->fillable);
        }

        //If is moddel sluggable
        if ( $this->sluggable != null )
            $this->fillable[] = 'slug';

        //Allow language foreign
        if ( $this->isEnabledLanguageForeign() )
            $this->fillable[] = 'language_id';
    }

    /*
     * Set date fields
     */
    protected function makeDateable()
    {
        foreach ($this->getFields() as $key => $field)
        {
            if ( $this->isFieldType($key, ['date', 'datetime', 'time']) && ! $this->hasFieldParam($key, 'multiple', true) )
                $this->dates[] = $key;
        }

        //Add dates
        $this->dates[] = 'published_at';
    }


    /*
     * Set selectbox field to automatic json format
     */
    protected function makeCastable()
    {
        foreach ($this->getFields() as $key => $field)
        {
            //Add cast attribute for fields with multiple select
            if ( (
                     $this->isFieldType($key, ['select', 'file', 'date', 'time'])
                     && $this->hasFieldParam($key, 'multiple', true)
                 )
                 || $this->isFieldType($key, 'json')
                 || $this->hasFieldParam($key, 'locale')
             )
                $this->casts[$key] = 'json';

            else if ( $this->isFieldType($key, 'checkbox') )
                $this->casts[$key] = 'boolean';

            else if ( $this->isFieldType($key, 'integer') || $this->hasFieldParam($key, 'belongsTo') )
                $this->casts[$key] = 'integer';

            else if ( $this->isFieldType($key, 'decimal') )
                $this->casts[$key] = 'float';

            else if ( $this->isFieldType($key, ['date', 'datetime', 'time']) )
                $this->casts[$key] = 'datetime';
        }

        //Add cast for order field
        if ( $this->isSortable() )
            $this->casts['_order'] = 'integer';

        //Casts foreign keys
        if ( is_array($relations = $this->getForeignColumn()) )
        {
            foreach ($relations as $key)
                $this->casts[$key] = 'integer';
        }
    }

    /*
     * Remove unneeded properties from model in administration
     */
    protected function removeHidden()
    {
        if ( ! Admin::isAdmin() )
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

    /**
     * Return fields converted from string (key:value|otherkey:othervalue) into array format
     * @return [array]
     */
    public function getFields($param = null, $force = false)
    {
        $with_options = count($this->withOptions) > 0;

        if ( $param !== null || $with_options === true )
            $force = true;

        //Field mutations
        if ( $this->_fields == null || $force == true )
        {
            $this->_fields = Fields::getFields( $this, $param, $force );

            $this->withoutOptions();
        }

        return $this->_fields;
    }

    /*
     * Return all model fields with options
     */
    public function getFieldsWithOptions($param = null, $force = false)
    {
        $this->withAllOptions();

        return $this->getFields($param, $force);
    }

    /*
     * Returns needed field
     */
    public function getField($key)
    {
        $fields = $this->getFields();

        if ( array_key_exists($key, $fields) )
            return $fields[$key];

        return null;
    }

    /*
     * Returns type of field
     */
    public function getFieldType($key)
    {
        $field = $this->getField($key);

        return $field['type'];
    }

    /*
     * Check column type
     */
    public function isFieldType($key, $types)
    {
        if ( is_string($types) )
            $types = [ $types ];

        return in_array( $this->getFieldType($key), $types);
    }

    /*
     * Returns maximum length of field
     */
    public function getFieldLength($key)
    {
        $field = $this->getField($key);

        if ( $this->isFieldType($key, ['file', 'password']) )
        {
            return 255;
        }

        //Return maximum defined value
        if ( array_key_exists('max', $field) )
            return $field['max'];

        //Return default maximum value
        return 255;
    }

    /*
     * Returns if field has required
     */
    public function hasFieldParam($key, $params, $paramValue = null)
    {
        if (!$field = $this->getField($key))
            return false;

        foreach (array_wrap($params) as $paramName) {
            if ( array_key_exists($paramName, $field) )
            {
                if ( $paramValue !== null )
                {
                    if ( $field[$paramName] === $paramValue )
                        return true;
                } else {
                    return true;
                }
            }
        }

        return false;
    }

    /*
     * Returns attribute of field
     */
    public function getFieldParam($key, $paramName)
    {
        if ( $this->hasFieldParam($key, $paramName) === false )
            return null;

        $field = $this->getField($key);

        return $field[$paramName];

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
     * Returns all base fields in one row
     */
    public function getBaseValues()
    {
        $fields = $this->getBaseFields();

        $data = [];

        foreach ($this->attributes as $key => $value)
        {
            if ( in_array($key, $fields) )
                $data[$key] = $value;
        }

        return $data;
    }

    /*
     * Returns migration date
     */
    public function getMigrationDate()
    {
        if ( !property_exists($this, 'migration_date') )
            return false;

        return $this->migration_date;
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

    /*
     * Returns property
     */
    public function getProperty($property, $row = null)
    {
        //Translates
        if ( in_array($property, ['name', 'title']) && $translate = trans($this->{$property}) )
            return $translate;

        //Object / Array
        elseif (in_array($property, ['fields', 'active', 'options', 'settings', 'buttons', 'reserved', 'insertable', 'editable', 'deletable', 'layouts', 'belongsToModel'])) {

            if ( method_exists($this, $property) )
                return $this->{$property}($row);

            if ( property_exists($this, $property) )
                return $this->{$property};

            return null;
        }

        return $this->{$property};
    }

    public function setProperty($property, $value)
    {
        $this->{$property} = $value;

        return $this;
    }

    //Returns schema with correct connection
    public function getSchema()
    {
        return Schema::connection( $this->getProperty('connection') );
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new \Gogol\Admin\Helpers\AdminCollection($models);
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
     Returns if form is in reversed mode, it mean that new rows will be added on end
     */
    public function isReversed()
    {
        if ( ! array_key_exists(2, $this->orderBy) || $this->orderBy[2] != true )
            return false;

        return in_array($this->orderBy[0], ['id', '_order']) && strtolower($this->orderBy[1]) == 'asc';
    }

    /*
     * Convert inline settings into array
     */
    private function assignArrayByPath(&$arr, $path, $value, $separator='.') {
        $keys = explode($separator, $path);

        foreach ($keys as $key) {
            $arr = &$arr[$key];
        }

        $row = [];

        if ( is_array($value) )
        {
            foreach ($value as $k => $v) {
                //Create multidimensional array
                $this->assignArrayByPath($row, $k, $v);
            }
        }

        $arr = is_array($value) ? $row : $value;
    }

    /*
     * Returns model settings in array
     */
    public function getModelSettings($separator = '.', &$arr = [])
    {
        $settings = (array)$this->getProperty('settings');

        $data = [];

        foreach ($settings as $path => $value)
        {
            $row = [];

            //Create multidimensional array
            $this->assignArrayByPath($row, $path, $value);

            $data = array_merge_recursive($data, $row);
        }

        return $data;
    }

    /*
     * Enable sorting
     */
    public function scopeAddSorting($query)
    {
        $column = $this->orderBy[0];

        if ( count(explode('.', $column)) == 1 )
            $column = $this->getTable() . '.' . $this->orderBy[0];

        /**
         * Add global scope for ordering
         */
        $query->orderBy($column, $this->orderBy[1]);
    }

    public function scopeWithPublished($query)
    {
        $query->where('published_at', '!=', null)->whereRAW('published_at <= NOW()');
    }

    /*
     * Save actual model row into history
     */
    public function historySnapshot($request, $original = null)
    {
        return Admin::getModel('ModelsHistory')->pushChanges($this, $request, $original);
    }


    /*
     * Foreach all rows in history, and get acutal data status
     */
    public function getHistorySnapshot($max_id = null, $id = null)
    {
        if (!($changes = ModelsHistory::where('table', $this->getTable())->where('row_id', $id ?: $this->getKey())->where(function($query) use ( $max_id ) {
            if ( $max_id )
                $query->where('id', '<=', $max_id);
        })->orderBy('id', 'ASC')->get()))
            return [];

        $data = [];

        foreach ($changes as $row)
        {
            $array = (array)json_decode($row['data']);

            foreach ($array as $key => $value)
            {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}