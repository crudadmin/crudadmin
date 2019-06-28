<?php

namespace Admin\Tests\App\Layouts;

use \Admin\Helpers\Layout;

class FormTopLayout extends Layout
{
    /*
     * Layout position
     * top/bottom/form-top/form-bottom/form-header/form-footer/table-header/table-footer
     */
    public $position = 'form-top';

    /*
     * On build blade layour
     */
    public function build()
    {
        return $this->renderVueJs('FormTopLayout.vue');
    }
}