<?php

namespace Admin\Contracts\Sitebuilder\Types;

use Admin\Contracts\Sitebuilder\SBType;
use Admin\Contracts\Sitebuilder\TypeInterface;
use Admin;

class Text extends SBType implements TypeInterface
{
    /**
     * Columns and group prefix for given type builder type
     *
     * @var  string
     */
    protected $prefix = 'text';

    /**
     * Returns icon name from font-awesome library
     *
     * @return  string
     */
    protected $icon = 'fa-italic';

    /*
     * Name of given sitebuilder
     */
    public function getName()
    {
        return _('Text');
    }

    /**
     * All registred fields into given group
     *
     * @return  array|Admin\Fields\Group
     */
    public function getFields()
    {
        return [
            'value' => 'name:Hodnota bloku|type:text|required'.(Admin::isEnabledLocalization() ? '|locale' : ''),
        ];
    }
}