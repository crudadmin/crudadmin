<?php

namespace Admin\Tests\App\Buttons;

use Admin\Helpers\Button;
use Illuminate\Support\Collection;

class SimpleMultipleButton extends Button
{
    /*
     * Here is your place for binding button properties for each row
     */
    public function __construct($row)
    {
        //Name of button on hover
        $this->name = 'SimpleMultipleButton';

        //Button classes
        $this->class = 'btn-default';

        //Button Icon
        $this->icon = 'fa-car';

        $this->type = 'multiple';
    }

    /*
     * Firing callback on press button
     */
    public function fireMultiple(Collection $rows)
    {
        foreach ($rows as $row)
        {
            $row->update([ 'field3' => 6 ]);
        }

        return $this->message('Your multiple action is done!');
    }
}