<?php

namespace Gogol\Admin\Traits;

use Localization;

trait FieldProperties
{
    /*
     * Which options can be loaded in getFields (eg data from db)
     */
    private $withOptions = [];

    /*
     * Save admin parent row into model
     */
    private $withParentRow = null;

    /*
     * Returns just base fields in getAdminAttributes
     */
    private $justBaseFields = false;

    /*
     * Skip belongsToMany properties in getAdminModelAttributes
     */
    private $skipBelongsToMany = false;

    /*
     * Field mutator for selects returns all options (also from db, etc...)
     */
    public function withAllOptions($set = null)
    {
        return $this->withOptions($set);
    }

    public function withOptions( $set = null )
    {
        //We want all fields options
        if ( $set === true ){
            $this->withOptions = array_keys($this->getFields());
        }

        //We want specifics fields options
        else if ( is_array($set) ){
            $this->withOptions = $set;
        }

        //We dont want any options
        else if ( $set === false ){
            $this->withOptions = [];
        }

        if ( $set !== null )
            return $this;

        return $this->withOptions;
    }

    /*
     * Returns just base fields of model
     */
    public function justBaseFields( $set = null )
    {
        if ( $set === true || $set === false )
            $this->justBaseFields = $set;

        return $this->justBaseFields;
    }

    /*
     * Save admin parent row into model
     */
    public function withModelParentRow($row)
    {
        $this->withParentRow = $row;
    }

    /*
     * Get admin parent row
     */
    public function getModelParentRow()
    {
        return $this->withParentRow;
    }

    /*
     * Return specific value in multi localization field by selected language
     * if translations are missing, returns default, or first available language
     */
    public function returnLocaleValue($object, $lang = null)
    {
        $slug = $lang ?: Localization::get()->slug;

        if ( ! $object || ! is_array($object) )
            return null;

        //If row has saved actual value
        if ( array_key_exists($slug, $object) && (!empty($object[$slug]) || $object[$slug] === 0) ){
            return $object[$slug];
        }

        //Return first available translated value in admin
        foreach ($object as $value) {
            if ( !empty($value) || $value === 0 )
                return $value;
        }

        return null;
    }
}