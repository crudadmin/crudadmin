<?php

namespace Admin\Tests\App\Buttons;

use Admin\Helpers\Button;
use Admin\Models\Model as AdminModel;

class SimpleButton extends Button
{
    /*
     * Here is your place for binding button properties for each row
     */
    public function __construct(AdminModel $row)
    {
        //Name of button on hover
        $this->name = 'SimpleButton';

        //Button classes
        $this->class = 'btn-default';

        //Button Icon
        $this->icon = 'fa-gift';
    }

    /*
     * Firing callback on press button
     */
    public function fire(AdminModel $row)
    {
        $row->update([ 'field3' => 5 ]);

        return $this->message('Your action is done!');
    }
}