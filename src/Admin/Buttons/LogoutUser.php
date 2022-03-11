<?php

namespace Admin\Admin\Buttons;

use Admin\Eloquent\AdminModel;
use Admin\Helpers\Button;
use Carbon\Carbon;

class LogoutUser extends Button
{
    /*
     * Here is your place for binding button properties for each row
     */
    public function __construct(AdminModel $row)
    {
        //Name of button on hover
        $this->name = 'Odhlásiť používateľa';

        //Button classes
        $this->class = 'btn-default';

        //Button Icon
        $this->icon = 'fa-sign-out';
    }

    public function question()
    {
        return $this->warning(_('Naozaj chcete ohdlásiť používateľa zo všetkých zariadení?'));
    }

    /*
     * Firing callback on press button
     */
    public function fire(AdminModel $row)
    {
        $date = Carbon::now();

        if ( $row->getKey() == admin()->getKey() ) {
            $row->setLogoutTimestamp($date);
        }

        $row->update([ 'logout_date' => $date ]);

        return $this->message(_('Používateľ bol úspešne odhláseny zo všetkých zariadení od tohto dátumu!'));
    }
}