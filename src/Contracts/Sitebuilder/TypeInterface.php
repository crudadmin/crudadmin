<?php

namespace Admin\Contracts\Sitebuilder;

interface TypeInterface
{
    /**
     * Columns and group prefix for given type builder type
     *
     * @return  string
     */
    public function getPrefix();

    /**
     * Returns icon name from font-awesome library
     *
     * @return  string
     */
    public function getIcon();

    /**
     * Name for given builder box group
     *
     * @return  string
     */
    public function getName();

    /**
     * All registred fields into given group
     *
     * @return  array|Admin\Fields\Group
     */
    public function getFields();
}