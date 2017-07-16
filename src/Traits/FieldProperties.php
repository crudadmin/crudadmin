<?php

namespace Gogol\Admin\Traits;

trait FieldProperties
{
    /*
     * Which options can be loaded in getFields (eg data from db)
     */
    private $withOptions = [];

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
}