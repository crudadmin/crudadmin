<?php

namespace Admin\Controllers;

use Admin\Models\ModelsHistory;
use Illuminate\Http\Request;
use Admin;

class HistoryController extends Controller
{
    /*
     * Return history rows
     */
    public function getHistory($table, $id)
    {
        $model = Admin::getModelByTable($table);

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
                            ->get(['id', 'data', 'user_id', 'created_at'])->map(function ($item) {
                                return $item->getMutatedAdminAttributes();
                            });

        return $rows;
    }

    public function removeFromHistory()
    {
        $model = Admin::getModelByTable(request('model'));

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
