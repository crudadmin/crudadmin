<?php

namespace Admin\Contracts\Sitebuilder\Types;

use Admin\Contracts\Sitebuilder\SBType;
use Admin\Contracts\Sitebuilder\TypeInterface;

class Image extends SBType implements TypeInterface
{
    /**
     * Columns and group prefix for given type builder type
     *
     * @var  string
     */
    protected $prefix = 'image';

    /**
     * Returns icon name from font-awesome library
     *
     * @return  string
     */
    protected $icon = 'fa-file-image';

    /*
     * Name of given sitebuilder
     */
    public function getName()
    {
        return _('Obrázok');
    }

    /**
     * All registred fields into given group
     *
     * @return  array|Admin\Fields\Group
     */
    public function getFields()
    {
        return [
            'value' => 'name:Obrázok|type:file|image|required',
        ];
    }
}