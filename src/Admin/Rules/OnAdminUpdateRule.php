<?php

namespace Admin\Admin\Rules;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;

class OnAdminUpdateRule extends AdminRule
{
    public function updating(AdminModel $row)
    {
        $this->checkUpdatingMyPermissions($row);
    }

    public function checkUpdatingMyPermissions($row)
    {
        if ( !admin() || $row->getKey() != admin()->getKey() ){
            return;
        }

        // Disable changing enabled state of own account
        if ( $row->hasEnabledSupport() && $row->enabled != $row->getOriginal('enabled') ){
            autoAjax()->error('Nie je možné deaktivovať vlastný účet.', 422)->throw();
        }

        // Disable changing admin permissions of own account
        if ( $row->hasAdminRoles() && $row->permissions != $row->getOriginal('permissions') ){
            autoAjax()->error('Nie je možné upravovať vlastne administrátorske práva.', 422)->throw();
        }
    }
}