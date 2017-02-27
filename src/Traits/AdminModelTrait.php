<?php

namespace Gogol\Admin\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\Filesystem;
use Gogol\Admin\Helpers\File;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Localization;
use Fields;
use Admin;
use Schema;
use DB;

trait AdminModelTrait
{
    private $_fillable = [ 'published_at' ];

    private $_fields = null;

    private $withAllOptions = false;

    /*
     * On calling method
     *
     * @see Illuminate\Database\Eloquent\Model
     */
    public function __call($method, $parameters)
    {
        //Checks for relationships
        if (!method_exists($this, $method) && $relation = $this->returnAdminRelationship($method))
            return $relation;

        //Checks for db relationship childrens into actual model
        if ( $relation = $this->checkForChildrenModels($method) )
            return $relation;

        return parent::__call($method, $parameters);
    }

    /*
     * On calling property
     *
     * @see Illuminate\Database\Eloquent\Model
     */
    public function __get($key)
    {
        return $this->getValue($key);
    }

    /*
     * Returns modified called property
     */
    public function getValue($key)
    {
        //Checks for relationship
        if (!property_exists($this, $key) && !method_exists($this, $key) && !array_key_exists($key, $this->attributes) && !$this->hasGetMutator($key) && $relation = $this->returnAdminRelationship($key, true))
            return $relation;

        //If is called field type file, then return file wrapper
        if ( $field = $this->getField($key) )
        {
            //Register file type response
            if ( $field['type'] == 'file' && !$this->hasGetMutator($key))
            {
                if ( $file = parent::__get($key) )
                {
                    if ( is_array($file) || $this->hasFieldParam($key, 'multiple', true) )
                    {
                        $files = [];

                        if ( !is_array($file) )
                            $file = [ $file ];

                        foreach ($file as $value)
                        {
                            if ( is_string($value) )
                                $files[] = new File( $value, $key, $this->getTable() );
                        }

                        return $files;
                    } else {
                        return new File( $file, $key, $this->getTable() );
                    }
                }

                return null;
            }
        }

        //If is called property with localization attribute, then add into called property language prefix
        else if ( Localization::isEnabled() && ($localization = Localization::get()) && $field = $this->getField($key.'_'.$localization->slug) ) {
            $key = $key.'_'.$localization->slug;
        }

        //Checks for db relationship childrens into actual model
        else if ( $relation = $this->checkForChildrenModels($key, true) ) {
            return $relation;
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

        //Save model
        return parent::save($options);
    }

    //Add fillable and dates fields
    public function initTrait()
    {
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
            if ( $field['type'] == 'date' )
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
            if ( $this->isFieldType($key, ['select', 'file']) && $this->hasFieldParam($key, 'multiple') )
                $this->casts[$key] = 'json';

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

    /**
     * Return fields converted from string (key:value|otherkey:othervalue) into array format
     * @return [array]
     */
    public function getFields($param = null, $force = false)
    {
        if ( $param !== null || $this->withAllOptions() === true)
            $force = true;

        //Field mutations
        if ( $this->_fields == null || $force == true )
        {
            $this->_fields = Fields::getFields( $this, $param, $force );
        }

        return $this->_fields;
    }

    /*
     * Makes properties from array to string
     */
    protected function fieldToString($field)
    {
        $data = [];

        foreach ( $field as $key => $value )
        {
            if ( $value === true ){
                $data[] = $key;
            } else if ( is_array( $value ) ){
                foreach ($value as $item) {
                    $data[] = $key . ':' . $item;
                }
            } else {
                $data[] = $key . ':' . $value;
            }
        }

        return $data;
    }

    /*
     * Removes admin properties in field from request
     */
    protected function removeAdminProperties($field)
    {
        //Remove admin columns
        foreach (Fields::getAttributes() as $key)
        {
            unset($field[$key]);
        }

        return $this->fieldToString($field);
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
     * Returns field before selected field, if is selected field first, returns last field
     */
    public function beforeFieldName($find_key)
    {
        $last = null;

        $i = 0;

        foreach ($this->getFields() as $key => $value)
        {
            if ( $key == $find_key && $i!==0 )
                break;

            $i++;

            $last = $key;
        }

        return $last;
    }

    /*
     * Returns if field has required
     */
    public function hasFieldParam($key, $paramName, $paramValue = null)
    {
        if (!$field = $this->getField($key))
            return false;

        if ( array_key_exists($paramName, $field) )
        {
            if ( $paramValue !== null )
            {
                return $field[$paramName] === $paramValue;
            }

            return true;
        } else
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
        foreach ((array)$this->skipDropping as $key)
        {
            $fields[] = $key;
        }

        //Add language id column
        if ($this->isEnabledLanguageForeign())
            $fields[] = 'language_id';

        if ( $this->sluggable != null )
        {
            $fields[] = 'slug';
        }

        if ( $this->sortable == true )
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

        return $fields;
    }

    protected function loadWithDependecies()
    {
        $with = [];

        //Load relationships
        if ( Admin::isAdmin() )
        {
            foreach ($this->getFields() as $key => $field)
            {
                if ( $this->hasFieldParam($key, 'belongsTo') )
                {
                    $with[] = substr($key, 0, -3);
                }

                if ( $this->hasFieldParam($key, 'belongsToMany') )
                {
                    $with[] = $key;
                }
            }
        }

        return $with;
    }

    /*
     * Returns all rows with base fields
     */
    public function getBaseRows($subid, $langid, $callback = null, $parent_table = null)
    {
        $fields = $this->maximum === 1 ? ['*'] : $this->getBaseFields();

        //Get model dependencies
        $with = $this->loadWithDependecies();

        //Get base columns from database with relationships
        $query = $this->getAdminRows()->select( $fields )->with($with);

        //Filter rows by language id and parent id
        $query->filterByParentOrLanguage($subid, $langid, $parent_table);

        if ( is_callable( $callback ) )
            call_user_func_array($callback, [$query]);

        $rows = [];

        foreach ($query->get() as $row)
        {
            $rows[] = $row->getAdminAttributes();
        };

        return $rows;
    }

    public function scopeFilterByParentOrLanguage($query, $subid, $langid, $parent_table = null)
    {
        if ( $langid > 0 )
            $query->localization($langid);

        if ( $subid > 0 ){
            $column = $this->getForeignColumn($parent_table);

            if ( $parent_table === null && count($column) == 1 )
            {
                $column = array_values($column)[0];
            }

            $query->where($column, $subid);
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
     * Returns if model has group in administration submenu
     */
    public function hasGroup()
    {
        return is_string( $this->group ) && !empty($this->group);
    }

    /*
     * Returns group for submenu
     */
    public function getGroup()
    {
        $config_groups = config('admin.groups');

        $group_name = $this->group;

        //Get group from config
        if ( $config_groups && array_key_exists($group_name, $config_groups) )
            $group_name = $config_groups[$group_name];

        return $group_name;
    }

    /*
     * Return all database relationship childs, models which actual model owns
     */
    public function getChilds()
    {
        $childs = [];

        $classname = get_class($this);

        $models = Admin::getAdminModels();

        foreach ($models as $model)
        {
            if ( ! $model->belongsToModel )
                continue;

            $belongsToModel = is_array($model->belongsToModel) ? $model->belongsToModel : [ $model->belongsToModel ];

            if ( in_array($classname, $belongsToModel) )
            {
                $childs[] = $model;
            }
        }

        return $childs;
    }

    /*
     * Returns migration date
     */
    public function getMigrationDate()
    {
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
        //Object / Array
        if (in_array($property, ['fields', 'options'])) {
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
    }

    /**
     * Convert the model's attributes to an array.
     *
     * @see Illuminate\Database\Eloquent\Model
     * @return array
     */
    public function getAdminAttributes()
    {
        $attributes = $this->attributesToArray();

        //Bing belongs to many values
        foreach ($this->getFields() as $key => $field)
        {
            if ( array_key_exists('belongsToMany', $field) )
            {
                $properties = $this->getRelationProperty($key, 'belongsToMany');

                //Get all admin modules
                $models = Admin::getAdminModelsPaths();

                foreach ($models as $path)
                {
                    //Find match
                    if ( strtolower( Str::snake(class_basename($path) ) ) == strtolower( $properties[5] ) )
                    {
                        $attributes[ $key ] = $this->{$key}->pluck( 'id' );
                    }
                }
            }
        }

        return $attributes;
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = $this->getAdminAttributes();

        //For administration is reversed way of merging arrays for multiselect relationships support
        if ( Admin::isAdmin() )
        {
            return array_merge($this->relationsToArray(), $attributes);
        }

        return array_merge($attributes, $this->relationsToArray());
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

    public function withAllOptions( $set = null )
    {
        if ( $set === true || $set === false )
            $this->withAllOptions = $set;

        return $this->withAllOptions;
    }

    public function makeDescription($field, $limit = 200)
    {
        $string = $this->{$field};

        return str_limit(strip_tags($string, $limit), $limit);
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
    public function isSortable()
    {
        if ( $this->minimum == 1 && $this->maximum == 1 )
            return false;

        return $this->getProperty('sortable');
    }
}