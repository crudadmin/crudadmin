<?php

namespace Gogol\Admin\Fields;

class Fields
{
    /*
     * This mutations will be applied into field in admin model
     */
    protected $mutations = [
        Mutations\FieldToArray::class,
        Mutations\AddGlobalRules::class,
        Mutations\AddAttributeRules::class,
        Mutations\AddSelectSupport::class,
        Mutations\BelongsToAttributeMutator::class,
        Mutations\AddLocalizationSupport::class,
        Mutations\UpdateDateFormat::class,
        Mutations\AddEmptyValue::class,
    ];

    /*
     * Registred custom admin attributes for fields
     */
    protected $attributes = [
         'name', 'title', 'type', 'placeholder', 'resize', 'hidden', 'disabled', 'orderBy', 'limit', 'removeFromForm', 'multirows', 'phone'
    ];

    /*
     * Model fields
     */
    protected $fields = [];

    /*
     * Model groups of fields
     */
    protected $groups = [];

    /*
     * Fields which will be removed
     */
    protected $remove = [];

    /*
     * Returns field attributes which are not includes in request rules, and are used for mutations
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /*
     * Add attribute for mutations
     */
    public function addAttribute($attribute)
    {
        if ( ! in_array($attribute, $this->attributes) )
            $this->attributes[] = $attribute;
    }

    /*
     * Add new mutation into list
     */
    public function addMutation( $namespace )
    {
        $this->mutations[] = $namespace;
    }

    /*
     * Checks if key of field is key for group fields
     */
    protected function isFieldGroup($field)
    {
        if ( is_string($field) )
            return false;

        if ( $field instanceof Group )
            return $field;

        return false;
    }

    /*
     * Push additional parameters into field from group
     */
    protected function pushParams($field, $add)
    {
        foreach ($add as $params)
        {
            $field = (new Mutations\FieldToArray)->update($field) + (new Mutations\FieldToArray)->update($params);
        }

        return $field;
    }

    /**
     * Returns all fields of model
     * @param  object  $model
     * @param  mixed  $param
     * @param  boolean $force
     * @return array
     */
    public function getFields($model, $param = null, $force = true)
    {
        //Get model table name
        $table = $model->getTable();

        //Buffer
        if ( array_key_exists($table, $this->fields) && $force === false )
        {
            return $this->fields[ $table ];
        }

        //Resets buffer
        $this->fields[ $table ] = [];
        $this->remove[ $table ] = [];

        //Fields from model
        $fields = $model->getProperty('fields', $param);

        foreach ($fields as $key => $field)
        {
            //If is group
            if ( $group = $this->isFieldGroup($field) )
            {
                //If group name is not set
                if ( ! $group->name )
                    $group->name($key);

                //Register groups into buffer
                $this->registerGroup( $group, $model );

                foreach ($group->fields as $field_key => $field_from_group)
                {
                    if ( count($group->add) > 0 )
                        $field_from_group = $this->pushParams( $field_from_group, $group->add );

                    //Create mutation on field
                    $this->registerField( $field_from_group, str_slug( $field_key, '_' ), $model );
                }
            } else {
                //Create mutation on field
                $this->registerField( $field, str_slug( $key, '_' ), $model );
            }
        }

        //Remove fields from mutations
        foreach ($this->remove[ $table ] as $key)
        {
            if ( array_key_exists($key, $this->fields[ $table ]) )
                unset($this->fields[ $table ][$key]);
        }

        return $this->fields[ $table ];
    }

    public function getFieldsGroups($model)
    {
        $table = $model->getTable();

        if ( ! array_key_exists($table, $this->groups) )
        {
            return false;
        }

        return $this->groups[ $table ];
    }

    /*
     * Register group into field buffer for groups
     */
    protected function registerGroup( $group, $model )
    {
        //Update and register field
        $this->groups[ $model->getTable() ][] = $group;
    }

    /*
     * Register field into fields buffer
     */
    protected function registerField( $field, $key, $model, $skip = [] )
    {

        //Field mutations
        foreach ($this->mutations as $namespace)
        {
            //Skip namespaces
            if ( in_array($namespace, $skip) )
                continue;

            if ( $response = $this->mutate($namespace, $field, $key, $model) )
            {
                $field = $response;
            }

            //Update and register field
            $this->fields[ $model->getTable() ][ $key ] = $field;
        }
    }

    public function mutate( $namespace, $field, $key = null, $model = null )
    {
        $mutation = new $namespace;

        //Updating field
        if ( method_exists($mutation, 'update') )
        {
            if ( ($response = $mutation->update($field, $key, $model)) && is_array($response) )
                $field = $response;
        }

        //Updating field
        $this->createFields($mutation, $field, $key, $model);

        //Updating field
        $this->removeFields($mutation, $field, $key, $model);

        //Register attributes into mutation
        $this->registerProperties($mutation);

        return $field;
    }

    protected function registerProperties($mutation)
    {
        if ( property_exists($mutation, 'attributes') )
        {
            $attributes = $mutation->attributes;

            //If is one attribute, than change string to array
            if ( is_string( $attributes ) )
                $attributes = [ $attributes ];

            foreach ( (array) $attributes as $attribute)
            {
                $this->addAttribute( $attribute );
            }
        }
    }

    /*
     * Register new fields from mutation
     */
    protected function createFields($mutation, $field, $key, $model)
    {
        if ( method_exists($mutation, 'create') )
        {
            $response = $mutation->create($field, $key, $model);

            if ( is_array($response) )
            {
                foreach ((array)$response as $key => $field)
                {
                    //Register field with all mutations, actual mutation will be skipped
                    $this->registerField($field, $key, $model, [ get_class($mutation) ]);
                }
            }
        }
    }

    /*
     * Remove fields from mutation
     */
    protected function removeFields($mutation, $field, $key, $model)
    {
        if ( method_exists($mutation, 'remove') )
        {
            $response = $mutation->remove($field, $key, $model);

            //Get model table name
            $table = $model->getTable();

            //Remove acutal key
            if ( $response === true )
                $this->remove[ $table ][] = $key;
            elseif ( is_string( $response ) )
                $this->remove[ $table ][] = $response;
            elseif ( is_array( $response ) ){
                foreach ((array)$response as $key)
                {
                    $this->remove[ $table ][] = $key;
                }
            }

        }
    }
}
?>