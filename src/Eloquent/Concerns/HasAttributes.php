<?php

namespace Admin\Eloquent\Concerns;

use Illuminate\Support\Str;
use Carbon\Carbon;
use Admin;

trait HasAttributes
{
    /*
     * Return attributes without mutates values
     */
    private $without_mutators = false;

    /**
     * Convert the model instance to an array.
     * In admin, do not convert end-point model by developer into array, without his modifications
     *
     * @return array
     */
    public function toArray()
    {
        //Skip modified attributes and get raw data in admin
        if ( Admin::isAdmin() ){
            return array_merge(parent::attributesToArray(), $this->relationsToArray());
        }

        return array_merge($this->attributesToArray(), $this->relationsToArray());
    }


    /**
     * Get the value of an attribute using its mutator.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function mutateAttribute($key, $value)
    {
        if ( $this->without_mutators === true )
            return $value;

        return parent::mutateAttribute($key, $value);
    }

    /**
     * Convert the model's attributes to an array.
     *
     * @see Illuminate\Database\Eloquent\Model
     * @return array
     */
    public function getAdminAttributes()
    {
        //Turn of mutating of attributes for admin results
        $this->without_mutators = true;

        //Get attributes without mutated values
        $attributes = parent::attributesToArray();

        $this->without_mutators = false;

        //Bing belongs to many values
        foreach ($this->getFields() as $key => $field)
        {
            /*
             * Update multiple values in many relationship
             */
            if ( array_key_exists('belongsToMany', $field) && $this->skipBelongsToMany === false )
            {
                $properties = $this->getRelationProperty($key, 'belongsToMany');

                //Get all admin modules
                $models = Admin::getAdminModelNamespaces();

                foreach ($models as $path)
                {
                    //Find match
                    if ( strtolower( Str::snake(class_basename($path) ) ) == strtolower( $properties[5] ) )
                    {
                        $attributes[ $key ] = $this->getValue($key)->pluck( 'id' );
                    }
                }
            }

            if ( array_key_exists($key, $attributes) )
            {
                /*
                 * Casts decimal format
                 */
                if ( $field['type'] == 'decimal' && !is_null($attributes[$key]))
                {
                    //Parse locale values
                    if ( $this->hasFieldParam($key, 'locale', true) )
                    {
                        foreach (array_wrap($attributes[$key]) as $k => $v)
                        {
                            if ( is_null($v) )
                                unset($attributes[$key][$k]);
                            else {
                                $attributes[$key][$k] = number_format($v, 2, '.', '');
                            }
                        }
                    }

                    //Parse simple values
                    else {
                        $attributes[$key] = number_format($attributes[$key], 2, '.', '');
                    }
                }

                /*
                 * Casts date/datetime/time values
                 */
                if ( ! $this->hasFieldParam($key, 'multiple', true) )
                    $this->castsAdminDatetimes($field, $key, $attributes);
            }
        }

        //Return just base fields
        if ( $this->maximum == 0 && $this->justBaseFields() === true )
        {
            return array_intersect_key($attributes, array_flip($this->getBaseFields()));
        }

        return $attributes;
    }

    /*
     * Casts datetime/date/time values
     */
    private function castsAdminDatetimes($field, $key, &$attributes)
    {
        //Skip locales values
        if ( $this->hasFieldParam($key, 'locale', true) )
            return;

        /*
         * Update to correct datetime format
         */
        if ( in_array($field['type'], ['date', 'datetime']) )
        {
            $attributes[$key] = $attributes[$key]
                                ? (new Carbon($attributes[$key]))->format( $field['date_format'] )
                                : null;
        }

        /*
         * Update to correct time format
         */
        if ( $field['type'] == 'time' )
        {
            $attributes[$key] = $attributes[$key]
                                ? (Carbon::createFromFormat('H:i:s', $attributes[$key]))->format( $field['date_format'] )
                                : null;
        }
    }

    /*
     * Overide admin attributes
     */
    public function getMutatedAdminAttributes()
    {
        $attributes = $this->getAdminAttributes();

        //Mutate attributes
        if ( method_exists($this, 'setAdminAttributes') )
            $attributes = $this->setAdminAttributes($attributes);

        return $attributes;
    }
}