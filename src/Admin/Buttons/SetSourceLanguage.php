<?php

namespace Admin\Admin\Buttons;

use Admin\Eloquent\AdminRule;
use Admin\Eloquent\AdminModel;

class SetSourceLanguage extends AdminRule
{
    private $column = 'is_source';

    //On all events
    public function fire(AdminModel $row)
    {
        //If is set default vat, then reset all others vats as default
        if ( $row->{$this->column} == true ){
            $row->newQuery()->where('id', '!=', $row->getKey())->update([
                $this->column => 0
            ]);
        }

        //If does not exist default vat
        else if ( $row->newQuery()->where($this->column, 1)->count() == 0 || $row->getOriginal($this->column) == 1 ) {
            $row->{$this->column} = 1;
        }
    }

    /*
     * Firing callback on delete row
     */
    public function delete(AdminModel $row)
    {
        if ( $row->{$this->column} == true ) {
            autoAjax()->error(_('Nie je možné vymazať zdrojovy záznam.'))->throw();
        }
    }
}