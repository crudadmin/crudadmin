<?php

namespace Admin\Helpers;

use Admin\Traits\VueComponent;

class Layout
{
    use VueComponent;

    /*
     * List of all available positions
     */
    public $available_positions = [
        'top', 'bottom',
        'form-top', 'form-bottom', 'form-header', 'form-footer',
        'table-header', 'table-footer'
    ];

    /*
     * Position of layout
     * top - before content table
     * bottom - after content table
     * form-top - start of the form
     * form-bottom - end of the form
     * form-header - header of the form
     * form-footer - footer of the form
     * table-header - header of the table
     * table-footer - footer of the table
     */
    public $position = 'top';

    /*
     * Where are stored VueJS components
     */
    protected function getComponentPaths()
    {
        return resource_path('views/admin/components/layouts');
    }
}

?>