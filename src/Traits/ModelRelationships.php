<?php

namespace Gogol\Admin\Traits;

use Admin;
use Illuminate\Support\Str;
use \Illuminate\Database\Eloquent\Collection;
use \Illuminate\Database\Eloquent\Model as BaseModel;

trait ModelRelationships
{
    /*
     * Relation key in admin buffer
     */
    protected function getAdminRelationKey( $method )
    {
        return '$relations.' . $this->getTable() . '.' . $method .'.'. ($this->exists ? $this->getKey() : 'global');
    }

    /*
     * Checks if is relation in laravel buffer or in admin buffer
     */
    public function isAdminRelationLoaded($key)
    {
        $loaded = parent::relationLoaded($key);

        if ( ! $loaded )
            $loaded = Admin::has( $this->getAdminRelationKey( $key ) );

        return $loaded;
    }

    /*
     * Get relation from laravel buffer if exists, or admin buffer
     */
    public function getRelationFromCache($key)
    {
        if ( parent::relationLoaded($key) ){
            return parent::getRelation($key);
        }

        return Admin::get( $this->getAdminRelationKey( $key ) );
    }

    /*
     * Set relation into laravel buffer, and also into admin buffer
     */
    public function setRelation($relation, $value)
    {
        Admin::save( $this->getAdminRelationKey( $relation ), $value );

        return parent::setRelation($relation, $value);
    }

    /*
     * Returns relationship for sibling model
     */
    protected function returnAdminRelationship($method, $get = false)
    {
        $method_lowercase = strtolower( $method );
        $method_snake = Str::snake($method);

        //Checks laravel buffer for relations
        if ( $this->isAdminRelationLoaded($method) )
        {
            $relation = $this->getRelationFromCache($method);
            if ( !is_array($relation) || !array_key_exists('type', $relation))
            {
                return $relation;
            }

            //Returns relationship builder
            if ( $get === false && $relation['get'] === false ) {
                return $relation['relation'];
            }

            //Returns items from already loaded relationship
            if ( $get == true && $relation['get'] == true )
            {
                if ( $relation['relation'] instanceof Collection || $relation['relation'] instanceof BaseModel ){
                    return $relation['relation'];
                } else {
                    return $this->returnRelationItems($relation);
                }
            }
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

                    return $this->relationResponse($method, 'belongsTo', $path, $get, $properties);
                }
            }
        }

        foreach ($models as $path)
        {
            $classname = strtolower( class_basename($path) );

            //Find match
            if ( $classname == $method_lowercase || str_plural($classname) == $method_lowercase )
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

                $modelBelongsToModel = is_array($model->getProperty('belongsToModel')) ? $model->getProperty('belongsToModel') : [ $model->getProperty('belongsToModel') ];
                $thisBelongsToModel = is_array($this->getProperty('belongsToModel')) ? $this->getProperty('belongsToModel') : [ $this->getProperty('belongsToModel') ];

                //Check if called model belongs to caller
                if ( !($isBelongsTo = in_array(get_class($model), $thisBelongsToModel)) && ! in_array(get_class($this), $modelBelongsToModel) )
                    break;

                $relationType = $isBelongsTo ? 'belongsTo' : 'hasMany';

                //If relationship can has only one child
                if ( $relationType == 'hasMany' && $model->maximum == 1 )
                    $relationType = 'hasOne';

                return $this->relationResponse($method, $relationType, $path, $get, [ 4 => $this->getForeignColumn( $model->getTable() ) ]);
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
            $relation_buffer = [
                'relation' => $relation,
                'get' => $get,
                'type' => $relationType,
            ];

            //If was relation called as property, and is only hasOne relationship, then return value
            if ( $get === true )
            {
                $relation_buffer['relation'] = $relation = $this->returnRelationItems($relation_buffer) ?: true;
            }

            //Save relation into laravel model buffer
            $this->setRelation($method, $relation_buffer);
        }

        return $relation;
    }

    /*
     * Returns foreign gey for parent model
     */
    public function getForeignColumn($model = null)
    {
        if ( $this->belongsToModel == null )
            return null;

        $belongsToModel = is_array($this->belongsToModel) ? $this->belongsToModel : [ $this->belongsToModel ];

        $columns = [];

        foreach ($belongsToModel as $parent)
        {
            $model_table_name = Str::snake(class_basename($parent));

            $columns[ str_plural($model_table_name) ] = $model_table_name . '_id';
        }

        if ( $model ) {
            return array_key_exists($model, $columns) ? $columns[$model] : null;
        }

        return $columns;
    }

    public function getBaseModelTable()
    {
        return Str::snake(class_basename($this));
    }


    /*
     * Returns properties of field with belongsTo or belongsToMany relationship
     */
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

    /*
     * Return type of data according to relation type, when is single relation, then method returns model,
     * else returns collection
     */
    public function returnRelationItems($relation)
    {
        //If is saved relationship with any result
        if ( $relation['relation'] === true )
            return true;

        return in_array($relation['type'], ['hasOne', 'belongsTo']) ?
            $relation['relation']->first()
            : $relation['relation']->get();
    }
}