<?php

namespace Admin\Tests\App\Buttons;

use Admin\Helpers\Button;
use Admin\Eloquent\AdminModel;

class QuestionButton extends Button
{
    /*
     * Here is your place for binding button properties for each row
     */
    public function __construct(AdminModel $row)
    {
        //Name of button on hover
        $this->name = 'Question Button';

        //Button classes
        $this->class = 'btn-default';

        //Button Icon
        $this->icon = 'fa-question';
    }

    /*
     * Firing callback on press button
     */
    public function ask(AdminModel $row)
    {
        return $this->message('Are you sure?');
    }

    /*
     * Firing callback on press button
     */
    public function fire(AdminModel $row)
    {
        $row->update([ 'field2' => 10 ]);

        return $this->message('Your action is done!');
    }
}