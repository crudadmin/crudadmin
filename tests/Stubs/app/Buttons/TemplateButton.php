<?php

namespace Gogol\Admin\Tests\App\Buttons;

use Gogol\Admin\Helpers\Button;
use Gogol\Admin\Models\Model as AdminModel;

class TemplateButton extends Button
{
    /*
     * Here is your place for binding button properties for each row
     */
    public function __construct(AdminModel $row)
    {
        //Name of button on hover
        $this->name = 'Template Button';

        //Button classes
        $this->class = 'btn-default';

        //Button Icon
        $this->icon = 'fa-th';
    }

    /*
     * Firing callback on press button
     */
    public function ask(AdminModel $row)
    {
        return $this
                ->title('Are you sure open template?')
                ->component(__DIR__.'/../../components/ButtonTemplate.vue');
    }

    /*
     * Firing callback on press button
     */
    public function fire(AdminModel $row)
    {
        $row->update([ 'field4' => request('mood') ]);

        return $this->message('Your custom template action is done!');
    }
}