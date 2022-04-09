<?php

namespace Admin\Admin\Rules;

use Admin;
use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;

class CanDeleteDefaultAdminLanguage extends AdminRule
{
    public function deleting(AdminModel $row)
    {
        if ( $row->slug === config('admin.locale') ){
            return autoAjax()->error(_('Predvolený jazyk administrácie nie je možné vymazať.'))->throw();
        }
    }

    public function unpublishing(AdminModel $row)
    {
        if ( $row->refresh()->slug === config('admin.locale') ){
            return autoAjax()->error(_('Predvolený jazyk administrácie nie je možné deaktivovať.'))->throw();
        }
    }
}