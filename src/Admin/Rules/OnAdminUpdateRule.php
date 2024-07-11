<?php

namespace Admin\Admin\Rules;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;

class OnAdminUpdateRule extends AdminRule
{
    public function updating(AdminModel $row)
    {
        $this->check($row);
    }

    public function check($row)
    {
        $isMe = $row->getKey() == admin()->getKey();

        if ( $isMe && $row->enabled == false ){
            autoAjax()->pushMessage('Nie je možné deaktivovať vlastný účet.');

            $row->enabled = true;
        }

        if ( $isMe && $row->permissions == false ){
            autoAjax()->pushMessage('Nie je možné upravovať vlastne administrátorske práva.');

            $row->permissions = true;
        }
    }
}