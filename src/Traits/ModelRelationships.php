<?php

namespace Gogol\Admin\Traits;

use Admin;
use Illuminate\Support\Str;
use \Illuminate\Database\Eloquent\Collection;
use \Illuminate\Database\Eloquent\Model as BaseModel;

trait ModelRelationships
{
    private $save_collection = null;

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
     * Returns relation from cache
     */
    private function returnRelationFromCache($method, $get)
    {
        $relation = $this->getRelationFromCache($method);

        //If is in relation buffer saved collection and not admin relation object
        if ( !is_array($relation) || !array_key_exists('type', $relation))
        {
            //If is saved collection, and requested is also collection
            if ( !(($is_collection = $relation instanceof Collection) && $get === false) )
                return $relation;

            //If is saved collection, but requested is object, then save old collection and return new relation object
            else if ( $is_collection )
                $this->save_collection = $relation;
        }

        //If is in relation buffer saved admin relation object
        else {
            //Returns relationship builder
            if ( $get === false || (!$this->exists && !parent::relationLoaded($method)) )
            {
                //Save old collection when is generating new object
                if ( $relation['relation'] instanceof Collection )
                    $this->save_collection = $relation['relation'];

                return $this->relationResponse(
                    $method,
                    $relation['type'],
                    $relation['path'],
                    $get === false ? false : true,
                    $relation['properties'],
                    $relation['relation']
                );
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

        return false;
    }

    /*
     * Return relation by belongsToMany field property
     */
    private function returnByBelongsToMany($method, $get, $models, $method_snake, $method_lowercase)
    {
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

        return false;
    }

    /*
     * Return relation by belongsTo field property
     */
    private function returnByBelongsTo($method, $get, $models, $method_snake)
    {
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

        return false;
    }

    /*
     * Return relations by fields from actual admin model
     */
    private function returnByFieldsRelations($method, $get, $models, $method_snake, $method_lowercase)
    {
        //Belongs to many relation
        if ( ($relation = $this->returnByBelongsToMany($method, $get, $models, $method_snake, $method_lowercase)) !== false )
            return $relation;

        //Belongs to
        if ( ($relation = $this->returnByBelongsTo($method, $get, $models, $method_snake)) !== false )
            return $relation;

        return false;
    }

    /*
     * Returns relationship for sibling model
     */
    protected function returnAdminRelationship($method, $get = false, $models = false)
    {
        $method_lowercase = strtolower( $method );
        $method_snake = Str::snake($method);

        //Checks laravel buffer for relations
        if ( $this->isAdminRelationLoaded($method) )
        {
            if ( ($cache = $this->returnRelationFromCache($method, $get)) !== false )
                return $cache;
        }

        //Get all admin modules
        if ( ! $models )
            $models = Admin::getAdminModelsPaths();

        /*
         * Return relations by defined fields in actual model
         */
        if ( ($relation = $this->returnByFieldsRelations($method, $get, $models, $method_snake, $method_lowercase)) !== false )
            return $relation;

        /*
         * Return relation from other way... search in all models, if some fields or models are note connected with actual model
         */
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

                $modelBelongsToModel = $model->getBelongsToRelation(true);
                $thisBelongsToModel = $this->getBelongsToRelation(true);

                //Check if called model belongs to caller
                if (
                    !($isBelongsTo = in_array(class_basename(get_class($model)), $thisBelongsToModel)) &&
                    ! in_array(class_basename(get_class($this)), $modelBelongsToModel)
                )
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
     * Return belongsToModel property in right format
     */
    public function getBelongsToRelation($base_name = false)
    {
        $items = array_filter(is_array($this->belongsToModel) ?
             $this->belongsToModel : [ $this->belongsToModel ]);

        if ( $base_name !== true )
            return $items;

        return array_map(function($item){
            if ( $item )
                return class_basename($item);
        }, $items);
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
            $relation = $this->belongsToMany( $path, $properties[3], $properties[6], $properties[7] )->orderBy($properties[3].'.id', 'asc');
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
                'type' => $relationType,
                'properties' => $properties,
                'path' => $path,
                'get' => $get,
            ];

            //If was relation called as property, and is only hasOne relationship, then return value
            if ( $get === true )
            {
                $relation_buffer['relation'] = $relation = $this->returnRelationItems($relation_buffer) ?: true;
            }

            //Save previous loaded collection into laravel admin buffer
            if ( $this->save_collection !== null ){
                $relation_buffer['relation'] = $this->save_collection;
                $relation_buffer['get'] = true;

                $this->save_collection = null;
            }

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

    /*
     * If is relation empty, owns TRUE value, so we need return null
     */
    protected function checkIfIsRelationNull($relation)
    {
        return $relation === true ? null : $relation;
    }

    public function getRelationshipNameBuilder($selector)
    {
        preg_match_all('/(?<!\\\\)[\:^]([0-9,a-z,A-Z$_]+)+/', $selector, $matches);

        if ( count($matches[1]) == 0 )
            $columns[] = $selector;
        else
            $columns = $matches[1];

        return $columns;
    }
}