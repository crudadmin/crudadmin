<?php

namespace Admin\Controllers;

use Admin;
use Admin\Controllers\Crud\CRUDController;
use Admin\Models\ModelsHistory;
use Illuminate\Http\Request;

class HistoryController extends CRUDController
{
    /*
     * Return history rows
     */
    public function getHistory($table, $id)
    {
        $model = $this->getModel($table);

        //If admin does not have permissions for given model
        if (
            admin()->hasAccess(ModelsHistory::class, 'read') == false
            || admin()->hasAccess($model, 'read') == false
        ){
            return autoAjax()->permissionsError();
        }

        $rows = ModelsHistory::where('table', $model->getTable())
                            ->where('row_id', $id)
                            ->with(['user' => function ($query) {
                                $query->select(['id', 'username']);
                            }])
                            ->select(['id', 'data', 'user_id', 'action', 'created_at'])
                            ->get()->map(function ($item) {
                                return $item->getMutatedAdminAttributes(true);
                            });

        //We wang log action after history fetch
        $model->logHistoryAction('history-list', [
            'row_id' => $id,
        ]);

        return $rows;
    }

    public function removeFromHistory()
    {
        $model = $this->getModel(request('model'));

        $row = ModelsHistory::where('table', $model->getTable())
                            ->where('id', request('id'))
                            ->first();

        //If admin does not have permissions for given model
        if (
            admin()->hasAccess(ModelsHistory::class, 'delete') == false
            || admin()->hasAccess($model, 'read') == false
            || !$row
        ){
            return autoAjax()->permissionsError();
        }

        $row->delete();

        return $this->getHistory($model->getTable(), $row->row_id);
    }
}
