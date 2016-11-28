<?php

namespace Gogol\Admin\Traits;

use Admin;
use Fields;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Gogol\Admin\Helpers\File;
use Gogol\Admin\Helpers\Sluggable;
use Localization;
use Illuminate\Database\Eloquent\Collection;
use DB;
use Carbon\Carbon;
use Validator;

trait AdminModelTrait
{
    private $_fillable = [ 'published_at' ];

    private $_fields = null;

    public function __call($method, $parameters)
    {
        //Checks for relationships
        if (!method_exists($this, $method) && $relation = $this->returnAdminRelationship($method))
            return $relation;

        //Checks for gallery
        if ( $gallery = $this->checkForGallery($method) )
            return $gallery;

        return parent::__call($method, $parameters);
    }

    /*
     * Update model data before saving
     */
    public function save(array $options = [])
    {
        $attributes = $this->attributes;

        //If model has needs sluggable field
        if ( $this->sluggable != null )
        {
            $attributes = (new Sluggable($attributes, $this))->get();
        }

        //If is creating new row
        if ( $this->exists == false )
        {
            //Add auto order incement into row, when row is not in database yet
            if ( $this->sortable == true && ! array_key_exists('_order', $attributes) )
            {
                $attributes['_order'] = $this->withTrashed()->count();
            }

            //Add auto publishing rows
            if ( $this->publishable == true && ! array_key_exists('published_at', $attributes) )
            {
                $attributes['published_at'] = Carbon::now()->toDateTimeString();
            }
        }

        //Override attributes
        $this->attributes = $attributes;

        //Save model
        return parent::save($options);
    }

    public function __get($key)
    {
        return $this->getValue($key);
    }

    public function getValue($key)
    {
        //Checks for relationship
        if (!property_exists($this, $key) && !method_exists($this, $key) && !array_key_exists($key, $this->attributes) && !$this->hasGetMutator($key) && $relation = $this->returnAdminRelationship($key, true))
            return $relation;

        if ( $field = $this->getField($key) )
        {
            //Register file type response
            if ( $field['type'] == 'file' && !$this->hasGetMutator($key))
            {
                if ( $file = parent::__get($key) )
                    return new File( $file, $key, $this->getTable() );
                else
                    return null;
            }
        } else if ( Localization::isEnabled() && ($localization = Localization::get()) && $field = $this->getField($key.'_'.$localization->slug) ) {
            $key = $key.'_'.$localization->slug;
        } else if ( $gallery = $this->checkForGallery($key, true) ){
            return $gallery;
        }

        return parent::__get($key);
    }

    protected function checkForGallery($method, $get = false)
    {
        if ( $method != 'gallery' )
            return false;

        $modelGallery = class_basename( str_plural( get_class($this) ) ) . 'Gallery';

        //Checks if actual model owns gallery, when yes, then returns gallery model relationship
        foreach( Admin::getAdminModelsPaths() as $path )
        {
            $classname = class_basename($path);

            if ( $modelGallery == $classname )
            {
                return $this->returnAdminRelationship($classname, $get);
            }
        }

        return false;
    }

    /*
     * Returns relationship for sibling model
     */
    public function returnAdminRelationship($method, $get = false)
    {
        $method_lowercase = strtolower( $method );
        $method_snake = Str::snake($method);

        //Checks laravel buffer for relations
        if ( $this->relationLoaded($method) && $get == true)
        {
            return $this->relations[$method];
        }

        //Get all admin modules
        $models = Admin::getAdminModelsPaths();

        //Belongs to many relation
        if ( $this->hasFieldParam($method_snake, 'belongsToMany') )
        {
            $properties = $this->getRelationProperty($method_snake, 'belongsToMany');

            foreach ($models as $path)
            {
                //Find match
                if ( strtolower( Str::snake( class_basename($path) ) ) == $properties[5] )
                {
                    return $this->relationResponse($method_snake, 'belongsToMany', $path, $get, $properties);
                }
            }
        }


        //Belongs to
        if ( $this->hasFieldParam($method_snake . '_id', 'belongsTo') )
        {
            //Get edited field key
            $field_key = $method_snake . '_id';

            //Get related table
            $foreign_table = explode(',', $this->getFieldParam($field_key, 'belongsTo'))[0];

            foreach ($models as $path)
            {
                //Find match
                if ( Str::snake( class_basename($path) ) == str_singular($foreign_table) )
                {
                    $properties = $this->getRelationProperty($field_key, 'belongsTo');

                    return $this->relationResponse($field_key, 'belongsTo', $path, $get, $properties);
                }
            }
        }

        foreach ($models as $path)
        {
            $classname = strtolower( class_basename($path) );

            //Find match
            if ( $classname == $method_lowercase || str_plural($classname) == $method )
            {
                $model = new $path;

                //If has belongs to many relation
                if ( $field = $model->getField( $this->getTable() ) )
                {
                    if ( array_key_exists('belongsToMany', $field) )
                    {
                        $properties = $model->getRelationProperty($this->getTable(), 'belongsToMany');

                        return $this->relationResponse($method, 'manyToMany', $path, $get, $properties);
                    }
                }

                //Checks all fields in model if has belongsTo relationship
                foreach ( $model->getFields() as $key => $field )
                {
                    if ( array_key_exists('belongsTo', $field) )
                    {
                        $properties = $model->getRelationProperty($key, 'belongsTo');

                        if ( $properties[0] == $this->getTable() )
                        {
                            return $this->relationResponse($method, 'hasMany', $path, $get);
                        }
                    }
                }

                //Check if called model belongs to caller
                if ( $model->getProperty('belongsToModel') != get_class($this) && $this->getProperty('belongsToModel') != get_class($model))
                    break;

                $relationType = $this->getProperty('belongsToModel') == get_class($model) ? 'belongsTo' : 'hasMany';

                //If relationship can has only one child
                if ( $relationType == 'hasMany' && $model->maximum == 1 )
                    $relationType = 'hasOne';

                return $this->relationResponse($method, $relationType, $path, $get, [ 4 => $this->getForeignColumn() ]);
            }
        }

        return false;
    }

    /*
     * Returns type of relation
     */
    protected function relationResponse($method, $relationType = false, $path, $get = false, $properties = [])
    {
        $relation = false;

        if ( $relationType == 'belongsTo' ){
            $relation = $this->belongsTo( $path, $properties[4] );
        } else if ( $relationType == 'belongsToMany' ){
            $relation = $this->belongsToMany( $path, $properties[3], $properties[6], $properties[7] );
        } else if ( $relationType == 'hasOne' )
            $relation = $this->hasOne( $path );
        else if ( $relationType == 'hasMany' )
            $relation = $this->hasMany( $path );
        else if ( $relationType == 'manyToMany' )
            $relation = $this->belongsToMany( $path, $properties[3], $properties[7], $properties[6] );

        if ( $relation )
        {
            //If was relation called as property, and is only hasOne relationship, then return value
            if ( $get === true )
            {
                $relation = in_array($relationType, ['hasOne', 'belongsTo']) ? $relation->first() : $relation->get();
            }

            //Save relation into laravel model buffer
            $this->setRelation($method, $relation);
        }

        return $relation;
    }

    //Add fillable and dates fields
    public function initTrait()
    {
        //Add fillable fields
        $this->makeFillable();

        //Add dates fields
        $this->makeDateable();

        //Add cast attributes
        $this->makeCastable();

        //Remove hidden when needed in admin
        $this->removeHidden();
    }

    /**
     * Set fillable property for laravel model from admin fields
     */
    public function makeFillable()
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
            $this->fillable[] = $this->getForeignColumn();

        //If is moddel sluggable
        if ( $this->sluggable != null )
            $this->fillable[] = 'slug';

        //Allow language foreign
        if ( $this->isEnabledLanguageForeign() )
            $this->fillable[] = 'language_id';
    }

    public function makeDateable()
    {
        foreach ($this->getFields() as $key => $field)
        {
            if ( $field['type'] == 'date' )
                $this->dates[] = $key;
        }

        //Add dates
        $this->dates[] = 'published_at';
    }


    public function makeCastable()
    {
        foreach ($this->getFields() as $key => $field)
        {

            //Add cast attribute for fields with multiple select
            if ( $field['type'] == 'select' && $this->hasFieldParam($key, 'multiple') )
                $this->casts[$key] = 'json';

        }
    }

    public function removeHidden()
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
        if ( count($this->hidden) > 0 && $column = $foreign_column = $this->getForeignColumn())
        {
            if ( in_array($column, $this->hidden) )
            {
                unset($this->hidden[array_search($column, $this->hidden)]);
            }
        }
    }

    /**
     * Return fields converted from string (key:value|otherkey:othervalue) into array format
     * @return [array]
     */
    public function getFields($param = null, $force = false)
    {
        if ( $param !== null )
            $force = true;

        //Field mutations
        if ( $this->_fields == null || $force == true )
        {
            $this->_fields = Fields::getFields( $this, $param, $force );
        }

        return $this->_fields;
    }

    public function getRules($row = null)
    {
        $fields = $this->getFields($row);

        $data = [];

        foreach ($fields as $key => $field)
        {
            //If is multiple file
            if ($this->isFieldType($key, 'file') && $this->hasFieldParam($key, 'multiple') && $field['multiple'] === true)
            {
                foreach (['multiple', 'array'] as $param)
                {
                    if ( array_key_exists($param, $field) )
                    {
                        unset($field[$param]);
                    }
                }

                //Add multiple validation support
                if ( ! $row )
                    $key = $key . '.*';
            }

            $data[$key] = $this->checkAdminProperties($field);

        }

        return $data;
    }

    protected function fieldToString($field)
    {
        $data = [];

        foreach ( $field as $key => $value )
        {
            if ( $value === true )
                $data[] = $key;
            else
                $data[] = $key . ':' . $value;
        }

        return $data;
    }

    protected function checkAdminProperties($field)
    {
        //Remove admin columns
        foreach (Fields::getAttributes() as $key)
        {
            unset($field[$key]);
        }

        return $this->fieldToString($field);
    }

    public function getField($key)
    {
        $fields = $this->getFields();

        if ( array_key_exists($key, $fields) )
            return $fields[$key];

        return null;
    }

    /*
     * Returns type of field
     * string/text/editor/select/integer/decimal/file/password
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
     * Returns if is field required
     */
    public function hasFieldParam($key, $paramName)
    {
        if (!$field = $this->getField($key))
            return false;

        if ( array_key_exists($paramName, $field) )
            return true;
        else
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
     * Returns short values for table into administration
     */
    public function getBaseFields($all = false)
    {
        $fields = ['id'];

        //If has foreign key, add column name to base fields
        if ( $this->getForeignColumn() )
            $fields[] = $this->getForeignColumn();

        foreach ($this->getFields() as $key => $field)
        {
            //If is not requested all columns, then skip fields with long values
            if ( $all === false && ( array_key_exists('hidden', $field) && $field['hidden'] == true ) || array_key_exists('belongsToMany', $field))
                continue;

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

    /*
     * Returns all rows with base fields
     */
    public function getBaseRows($from_id = 0)
    {
        $fields = $this->getBaseFields();

        $query = $this->select( $fields )->orderBy('id', 'asc')->where('id', '>', $from_id);

        //Limit for models with big data in first data request
        if ( $from_id == -1 )
            $query = $query->limit(200);

        return $query->get();
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

    public function filePath($key)
    {
        return 'uploads/' . $this->getTable() . '/' . $key;
    }

    public function hasGroup()
    {
        return is_string( $this->group ) && !empty($this->group);
    }

    public function getGroup()
    {
        $config_groups = config('admin.groups');

        $group_name = $this->group;

        //Get group from config
        if ( $config_groups && array_key_exists($group_name, $config_groups) )
            $group_name = $config_groups[$group_name];

        return $group_name;
    }

    public function getChilds()
    {
        $childs = [];

        $classname = get_class($this);

        $models = Admin::getAdminModels();

        foreach ($models as $model)
        {
            if ( $model->belongsToModel == $classname )
            {
                $childs[] = $model;
            }
        }

        return $childs;
    }

    public function getBaseModelTable()
    {
        return Str::snake(class_basename($this));
    }

    public function getForeignColumn($model = null)
    {
        if ( $this->belongsToModel == null && $model===null )
            return null;

        $parent = $model ? $model : new $this->belongsToModel;

        $model_table_name = $parent->getBaseModelTable();

        return $model_table_name . '_id';
    }

    public function getMigrationDate()
    {
        return $this->migration_date;
    }

    public function isEnabledLanguageForeign()
    {
        if ( ( $this->getBaseModelTable()!='language' && $this->belongsToModel == null && $this->localization === true || $this->localization === true ) && Admin::isEnabledMultiLanguages())
            return true;

        return false;
    }

    public function getProperty($property, $row = null)
    {
        //Object / Array
        if (in_array($property, ['fields', 'options'])) {
            return method_exists($this, $property) ? $this->{$property}($row) : $this->{$property};
        }

        return $this->{$property};
    }

    public function getRelationProperty($key, $relation)
    {
        $field = $this->getField($key);

        $properties = explode(',', $field[$relation]);
        //If is not defined references column for other table
        if ( count($properties) == 1 )
            $properties[] = 'NULL';

        if ( count($properties) == 2 )
            $properties[] = 'id';

        if ( $relation == 'belongsToMany' )
        {
            //Table names in singular
            $tables = [
                str_singular($this->getTable()),
                str_singular($properties[0])
            ];

            //Pivot table name
            $pivot_table = $tables[1] . '_' . $tables[0] . '_' . $key;

            //Add pivot table into properties
            $properties[] = $pivot_table;
            $properties[] = $tables[0];
            $properties[] = $tables[1];
            $properties[] = $tables[0] . '_id';
            $properties[] = $tables[1] . '_id';
        } else {
            $properties[] = str_singular( $properties[0] );
            $properties[] = $key;
        }

        return $properties;
    }

    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        //Automaticaly binded belongsToMany relationships only for administration
        if ( ! Admin::isAdmin() )
            return $attributes;

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
                    if ( strtolower( class_basename($path) ) == strtolower( $properties[5] ) )
                    {
                        $rows = DB::table($properties[3])->where( $properties[6], $this->getKey() )->lists( $properties[7] );

                        $attributes[ $key ] = $rows;
                    }
                }
            }
        }

        return $attributes;
    }

    public function validateRequest($row = null)
    {
        $rules = $this->getRules( $row );

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            return redirect( url()->previous() )
                        ->withErrors($validator)
                        ->withInput();
        }

        return false;
    }
}