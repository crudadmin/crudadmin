<?php

namespace Gogol\Admin\Helpers;

use Gogol\Admin\Traits\FieldComponent;

class Layout
{
    use FieldComponent;

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
}

?>