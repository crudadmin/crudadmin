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
        Mutations\InterfaceRules::class,
        Mutations\BelongsToAttributeMutator::class,
        Mutations\AddSelectSupport::class,
        Mutations\AddLocalizationSupport::class,
        Mutations\UpdateDateFormat::class,
        Mutations\AddEmptyValue::class,
    ];

    /*
     * Registred custom admin attributes for fields
     */
    protected $attributes = [
         'name', 'title', 'type', 'placeholder', 'resize', 'hidden', 'disabled',
         'orderBy', 'limit', 'removeFromForm', 'multirows', 'phone_link', 'unique_db',
         'index', 'invisible', 'unsigned', 'component', 'column_name',
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
        $this->groups[ $table ] = [];
        $this->remove[ $table ] = [];

        //Fields from model
        $fields = $model->getProperty('fields', $param);

        //Put fields into group, if are represented as array
        $fields = is_array($fields) ? Group::fields($fields, null, 'default') : $fields;

        //Get actual model mutation
        $mutation = $this->addFieldsMutationIntoModel($model, $param);

        //Register fields from groups
        $this->manageGroupFields($model, 0, $fields, null, $mutation);

        return $this->fields[ $table ];
    }

    /*
     * Register mutations of fields in actual model
     */
    private function addFieldsMutationIntoModel($model, $param)
    {
        $builder = new FieldsMutationBuilder;

        if ( method_exists($model, 'mutateFields') )
            $model->mutateFields($builder, $param);

        return $builder;
    }

    /*
     * Modify group by id
     */
    private function mutateGroup($group, $mutation)
    {
        if ( ! $group->id || count($mutation->groups) == 0 || !array_key_exists($group->id, $mutation->groups) )
            return $group;

        $mutation->groups[$group->id]($group);

        return $group;
    }

    /*
     * Insert field/group on position
     */
    private function insertInto($where, $key, $fields, $mutation)
    {
        foreach ($mutation->{$where} as $position_key => $add_before) {
            if ( $key === $position_key ){
                foreach ($add_before as $add_key => $add_field)
                    $fields = $this->pushFieldOrGroup($fields, $add_key, $add_field, $mutation);
            }
        }

        return $fields;
    }

    /*
     * Add field, or modified group into fields list
     */
    private function pushFieldOrGroup($fields, $key, $field, $mutation)
    {
        if ( $this->isFieldGroup($field) ){
            $group = $this->mutateGroup($field, $mutation, $mutation);

            if ( is_numeric($key) )
                $fields[] = $group;
            else
                $fields[$key] = $group;
        } else {
            $fields[$key] = $field;
        }

        return $fields;
    }

    /*
     * Add before/after new field or remove fields for overriden admin model
     */
    private function mutateGroupFields($items, $mutation, $parent_group = null)
    {
        $fields = [];

        foreach ($items as $key => $field)
        {
            //Add before field
            $fields = $this->insertInto('before', $key, $fields, $mutation);

            //Add if is not removed
            if ( ! in_array($key, $mutation->remove) )
                $fields = $this->pushFieldOrGroup($fields, $key, $field, $mutation);

            //Add after field
            $fields = $this->insertInto('after', $key, $fields, $mutation);
        }

        //Push new fields, groups... or replace existing fields. Into first level of fields
        if ( ! $parent_group )
        {
            foreach ($mutation->push as $key => $field) {
                $fields = $this->pushFieldOrGroup($fields, $key, $field, $mutation);
            }
        }

        return $fields;
    }

    /*
     * Register fields from all groups and infinite level of sub groups or tabs
     * Also rewrite mutated fields into groups
     */
    private function manageGroupFields($model, $key, $field, $parent_group = null, FieldsMutationBuilder $mutationBuilder)
    {
        //If is group
        if ( $group = $this->isFieldGroup($field) )
        {
            //If group name is not set
            if ( ! $group->name && !is_numeric($key) )
                $group->name($key);

            $fields = [];

            //Actual group will inherit parent groups add-ons
            if ( $parent_group && count($parent_group->add) > 0 )
                $group->add = array_merge($group->add, $parent_group->add);

            //Register sub groups or sub fields
            foreach ($this->mutateGroupFields($group->fields, $mutationBuilder, $parent_group) as $field_key => $field_from_group)
            {
                $mutation_previous = isset($mutation_previous) ? $mutation_previous : $this->fields[$model->getTable()];

                $mutation = $this->manageGroupFields($model, $field_key, $field_from_group, $group, $mutationBuilder);

                //If is group in fields list
                if ( $mutation instanceof Group ){
                    $fields[] = $mutation;

                    $mutation_previous = $this->fields[$model->getTable()];
                }

                //Add new fields into group from fields mutations
                else {
                    foreach (array_diff_key($mutation, $mutation_previous) as $key => $field) {
                        $fields[] = $key;
                    }

                    $mutation_previous = $mutation;
                }
            }

            $group->fields = $fields;

            //Register group into buffer
            if ( ! $parent_group )
                $this->registerGroup( $group, $model );

            return $group;
        } else {
            if ( $parent_group && count($parent_group->add) > 0 ){
                $field = $this->pushParams( $field, $parent_group->add );
            }

            //Create mutation on field
            return $this->registerField( $field, str_slug( $key, '_' ), $model );
        }
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
        $table = $model->getTable();

        //Field mutations
        foreach ($this->mutations as $namespace)
        {
            //Skip namespaces
            if ( in_array($namespace, $skip) )
                continue;

            if ( $response = $this->mutate($namespace, $field, $key, $model) )
                $field = $response;

            //Update and register field
            $this->fields[ $table ][ $key ] = $field;
        }

        //If field need to be removed
        if ( in_array($key, (array)$this->remove[$table]) )
            unset($this->fields[ $table ][ $key ]);

        return $this->fields[ $table ];
    }

    public function mutate( $namespace, $field, $key = null, $model = null )
    {
        $mutation = new $namespace;

        if ( $mutation instanceof \Gogol\Admin\Fields\Mutations\MutationRule )
        {
            $mutation->setFields($this->fields[ $model->getTable() ]);
            $mutation->setField($field);
            $mutation->setKey($key);
        }

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

        //Register attributes from mutation
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