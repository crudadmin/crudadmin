<?php

namespace Admin\Eloquent\Concerns;

trait HasSiteTree
{
    /*
     * Has sitetree support?
     */
    protected $sitetree = false;

    /*
     * Which columns should be loaded into sitetree
     */
    public function siteTreeColumns()
    {
        $columns = [
            $this->getKeyName(),
            $this->getProperty('sitetree')
        ];

        //Add slug column
        if ( $this->getProperty('sluggable') ){
            $columns[] = 'slug';
        }

        return $columns;
    }

    /**
     * Scope
     *
     * @param  Builder  $query
     */
    public function scopeOnSiteTreeLoad($query)
    {

    }

    /**
     * Build sitetree url
     *
     * @return  string
     */
    public function getTreeAction()
    {
        // return action('...', $this->getSlug());
    }
}