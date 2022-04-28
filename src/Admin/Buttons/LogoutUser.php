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
        $this->name = _('Odhlásiť používateľa');

        //Button classes
        $this->class = 'btn-default';

        //Button Icon
        $this->icon = 'fa-sign-out';

        $this->active = $this->hasAccess();
    }

    private function hasAccess()
    {
        return admin()->hasAccess(admin(), 'logout');
    }

    public function question()
    {
        return $this->warning(_('Naozaj chcete odhlásiť používateľa zo všetkých zariadení?'));
    }

    /*
     * Firing callback on press button
     */
    public function fire(AdminModel $row)
    {
        if ( $this->hasAccess() === false ){
            return $this->error(_('Nemáte prístup k tejto akcii.'));
        }

        $date = Carbon::now();

        if ( $row->getKey() == admin()->getKey() ) {
            $row->setLogoutTimestamp($date);
        }

        $row->update([ 'logout_date' => $date ]);

        return $this->message(_('Používateľ bol úspešne odhláseny zo všetkých zariadení od tohto dátumu!'));
    }
}