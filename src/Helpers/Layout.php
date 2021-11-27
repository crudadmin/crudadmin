<?php

namespace Admin\Helpers;

use Admin\Eloquent\Concerns\VueComponent;

class Layout
{
    use VueComponent;

    /*
     * List of all available positions
     */
    public $available_positions = [
        'top', 'bottom',
        'form-before', 'form-after', 'form-top', 'form-bottom', 'form-header', 'form-header-left', 'form-header-right', 'form-footer',
        'table-before', 'table-after', 'table-header', 'table-header-actions', 'table-footer',
        'actions-grid',
    ];

    /*
     * Position of layout
     * top - before content table
     * bottom - after content table
     * form-top - start of the form
     * form-bottom - end of the form
     * form-header - header of the form
     * form-header-left - header of the form in action bar (left)
     * form-header-right - header of the form in action bar (right)
     * form-footer - footer of the form
     * table-header - header of the table
     * table-header-actions - action bar
     * table-footer - footer of the table
     * actions-grid - Next to grid switcher
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
