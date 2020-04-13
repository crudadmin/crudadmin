<?php

namespace Admin\Contracts\Sitebuilder\Types;

use Admin\Contracts\Sitebuilder\SBType;
use Admin\Contracts\Sitebuilder\TypeInterface;
use Admin;

class Editor extends SBType implements TypeInterface
{
    /**
     * Columns and group prefix for given type builder type
     *
     * @var  string
     */
    protected $prefix = 'editor';

    /**
     * Returns icon name from font-awesome library
     *
     * @return  string
     */
    protected $icon = 'fa-file-alt';

    /*
     * Name of given sitebuilder
     */
    public function getName()
    {
        return _('Textový editor');
    }

    /**
     * All registred fields into given group
     *
     * @return  array|Admin\Fields\Group
     */
    public function getFields()
    {
        return [
            'value' => 'name:Obsah bloku|type:editor|required'.(Admin::isEnabledLocalization() ? '|locale' : ''),
        ];
    }
}