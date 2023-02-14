<?php

namespace Admin\Eloquent\Concerns;

use Admin;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Arr;

trait HasAttributes
{
    /*
     * Return attributes without mutates values
     */
    private $withoutMutators = false;

    /**
     * Convert the model instance to an array.
     * In admin, do not convert end-point model by developer into array, without his modifications.
     *
     * @return array
     */
    public function toArray()
    {
        //Skip modified attributes and get raw data in admin
        if (Admin::isAdmin()) {
            return array_merge(parent::attributesToArray(), $this->relationsToArray());
        }

        return array_merge($this->attributesToArray(), $this->relationsToArray());
    }

    /**
     * Check if mutator is whitelisted
     *
     * @param  string  $key
     *
     * @return  bool
     */
    private function isMutatorWhitelisted($key)
    {
        $whitelisted = $this->withoutMutators;

        if ( is_array($whitelisted) ){
            return in_array($key, $whitelisted);
        }

        return true;
    }

    /**
     * Determine if a get mutator exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        if ($this->isMutatorWhitelisted($key) === false) {
            return false;
        }

        return parent::hasGetMutator($key);
    }

    /**
     * Determine if a "Attribute" return type marked get mutator exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasAttributeGetMutator($key)
    {
        if ($this->isMutatorWhitelisted($key) === false) {
            return false;
        }

        return parent::hasAttributeGetMutator($key);
    }

    /**
     * Get the mutated attributes for a given instance.
     *
     * @return array
     */
    public function getMutatedAttributes()
    {
        $mutatedAttributes = parent::getMutatedAttributes();

        $whitelisted = $this->withoutMutators;
        if ( is_array($whitelisted) ){
            return array_intersect($whitelisted, $mutatedAttributes);
        }

        return $mutatedAttributes;
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
        if ($this->isMutatorWhitelisted($key) === false) {
            return $value;
        }

        return parent::mutateAttribute($key, $value);
    }

    /**
     * Convert the model's attributes to an array.
     *
     * @see Illuminate\Database\Eloquent\Model
     * @return array
     */
    private function getAdminAttributes()
    {
        //Turn of mutating of attributes for admin results
        //Enable only appended attributes
        $this->withoutMutators = $this->getAppends();

        $fields = $this->getFields();

        $this->setVisibleAdminAttributes();

        $this->castAdminAttributes();

        //Get attributes without mutated values
        $attributes = parent::attributesToArray();

        $this->withoutMutators = false;

        return $attributes;
    }

    private function getAdminCasts()
    {
        $casts = [];

        $fields = $this->getFields();

        //Bind belongs to many values
        foreach ($fields as $key => $field) {
            /*
             * Update multiple values in many relationship
             */
            if (array_key_exists('belongsToMany', $field) && $this->skipBelongsToMany === false) {
                $casts[$key] = \Admin\Eloquent\Casts\BelongsToManyCast::class;
            }

            /*
             * Casts decimal format
             */
            if ($field['type'] == 'decimal') {
                $casts[$key] = \Admin\Eloquent\Casts\DecimalCast::class;
            }

            if ($field['type'] == 'date') {
                $casts[$key] = \Admin\Eloquent\Casts\DateCast::class;
            }
        }

        return $casts;
    }

    public function callWithoutCasts($callback, $except = null)
    {
        $casts = $this->casts;

        $this->casts = $except ? array_diff_key($casts, array_flip(array_wrap($except))) : [];

        $value = $callback();

        $this->casts = $casts;

        return $value;
    }

    private function castAdminAttributes()
    {
        $casts = $this->getAdminCasts();

        foreach ($casts as $key => $cast) {
            $this->casts[$key] = $cast;
        }
    }

    private function setVisibleAdminAttributes()
    {
        //Return just base fields
        if ($this->justBaseFields() === true) {
            $this->makeVisible(
                $this->getBaseFields()
            );

        }

        //Add belongsToMany if is visible
        foreach ($this->getArrayableItems($this->getFields()) as $key => $field) {
            if ( $field['belongsToMany'] ?? null ){
                $this->append($key);
            }
        }
    }

    private function runAdminAttributesMutators($events, $attributes = [])
    {
        foreach ($events as $eventName => $enabled) {
            if ( $enabled == false ){
                continue;
            }

            if (method_exists($this, $eventName)) {
                $attributes = $this->{$eventName}($attributes);
            }

            $this->runAdminModules(function($module) use ($eventName, &$attributes) {
                if ( method_exists($module, $eventName) ) {
                    $attributes = $module->{$eventName}($attributes);
                }
            });
        }

        return $attributes;
    }

    /*
     * Overide admin attributes
     */
    public function getMutatedAdminAttributes($isColumns = false, $isRow = false)
    {
        $this->runAdminAttributesMutators([
            'setAdminResponse' => true,
            'setAdminRowsResponse' => $isColumns,
            'setAdminRowResponse' => $isRow,
        ]);

        /**
         * Render attributes
         */
        $attributes = $this->getAdminAttributes();

        $attributes = $this->runAdminAttributesMutators([
            'setAdminAttributes' => true,
            'setAdminRowsAttributes' => $isColumns,
            'setAdminRowAttributes' => $isRow,
        ], $attributes);

        return $attributes;
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        //We want file name from file helper
        if ( $value instanceof Admin\Core\Helpers\File ){
            $value = $value->filename;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Get the model's raw original attribute values.
     * Backward support for Laravel 6.0
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed|array
     */
    public function getRawOriginal($key = null, $default = null)
    {
        return Arr::get($this->original, $key, $default);
    }
}
