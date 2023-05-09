<?php

namespace Admin\Admin\Buttons;

use Admin\Eloquent\AdminModel;
use Admin\Helpers\Button;
use Admin;

class HistoryButton extends Button
{
    /*
     * Here is your place for binding button properties for each row
     */
    public function __construct(AdminModel $row = null)
    {
        //Name of button on hover
        $this->name = trans('admin::admin.history.changes');

        //Button classes
        $this->icon = 'fa-history';

        $this->active = $row->hasHistory() && $row->history_rows_count > 0;
    }


    /*
     * Firing callback on press button
     */
    public function fire(AdminModel $row)
    {
        //If admin does not have permissions for given model
        if (
            !admin()->hasAccess(Admin::getModel('ModelsHistory')::class, 'read')
            || !admin()->hasAccess($row, 'read')
        ){
            return autoAjax()->permissionsError()->throw();
        }

        $rows = $row->historyRows()
                    ->with(['user' => function ($query) {
                        $query->select(['id', 'username']);
                    }])
                    ->select(['id', 'data', 'user_id', 'action', 'created_at'])
                    ->get()
                    ->each
                        ->makeHidden(['data'])
                        ->append([
                            'actionName', 'changedFields',
                        ]);

        //We wang log action after history fetch
        $row->logHistoryAction('history-list', [
            'row_id' => $row->getKey(),
        ]);

        if ( $rows->count() <= 1 ){
            return $this->warning(trans('admin::admin.no-changes'));
        }

        return $this->title(trans('admin::admin.history.changes'))->component('HistoryModal', [
            'rows' => $rows,
        ]);
    }
}