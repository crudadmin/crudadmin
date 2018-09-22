<?php

namespace Gogol\Admin\Helpers;

use Gogol\Admin\Traits\FieldComponent;

class Layout
{
    use FieldComponent;

    /*
     * Position of layour
     * top - before content table
     * bottom - after content table
     * form-top - start of form
     * form-bottom - end of form
     */
    public $position = 'top';

    public function renderVueJs($template)
    {
        $path = resource_path('views/'.$template);

        return $this->renderFieldComponent($path);
    }
}

?>